<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $roleName = $this->roleName($request);
        $query = $this->scopedQuery($request, $roleName);

        if (NotificationService::canViewConservationRefs($roleName)) {
            $query->with(['animal', 'zone', 'device']);
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        if ($request->boolean('onlyUnread') || $request->boolean('unread')) {
            $query->where('is_read', false);
        }
        if ($request->filled('type') || $request->filled('notification_type')) {
            $query->where('notification_type', $request->input('notification_type', $request->input('type')));
        }

        return $this->list($query->orderByDesc('created_at')->orderByDesc('notif_id'));
    }

    public function show(Request $request, int $id)
    {
        $roleName = $this->roleName($request);
        $notification = $this->own($request, $id, $roleName);

        if (NotificationService::canViewConservationRefs($roleName)) {
            $notification->load(['animal', 'zone', 'device', 'report', 'prediction']);
        }

        return $this->success($notification);
    }

    public function unreadCount(Request $request)
    {
        $roleName = $this->roleName($request);

        return $this->success([
            'unread' => $this->scopedQuery($request, $roleName)->where('is_read', false)->count(),
            'allowed_types' => NotificationService::allowedTypesForRole($roleName),
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $notification = $this->own($request, $id, $this->roleName($request));
        $notification->update(['is_read' => true]);

        return $this->success($notification->fresh(), 'Notifikasi ditandai dibaca');
    }

    public function markAllAsRead(Request $request)
    {
        $count = $this->scopedQuery($request, $this->roleName($request))
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->success(['updated' => $count], 'Semua notifikasi ditandai dibaca');
    }

    public function destroy(Request $request, int $id)
    {
        $this->own($request, $id, $this->roleName($request))->delete();

        return $this->success(null, 'Notifikasi dihapus');
    }

    public function deleteRead(Request $request)
    {
        $count = $this->scopedQuery($request, $this->roleName($request))
            ->where('is_read', true)
            ->delete();

        return $this->success(['deleted' => $count], 'Notifikasi yang sudah dibaca dihapus');
    }

    private function roleName(Request $request): ?string
    {
        return $request->user()?->role?->name_role;
    }

    private function scopedQuery(Request $request, ?string $roleName): Builder
    {
        return Notification::query()
            ->where('user_id', $request->user()->user_id)
            ->whereIn('notification_type', NotificationService::allowedTypesForRole($roleName));
    }

    private function own(Request $request, int $id, ?string $roleName): Notification
    {
        return $this->scopedQuery($request, $roleName)->findOrFail($id);
    }
}
