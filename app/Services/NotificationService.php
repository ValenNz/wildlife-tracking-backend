<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public const FIELD_ROLES = ['konservasionis_lapangan', 'administrator_sistem'];
    public const RESEARCH_ROLES = ['peneliti_ekologi', 'administrator_sistem'];

    private const ROLE_ALLOWED_TYPES = [
        'administrator_sistem' => ['risk_zone', 'device_alert', 'system'],
        'konservasionis_lapangan' => ['risk_zone', 'device_alert', 'system'],
        'peneliti_ekologi' => ['risk_zone', 'device_alert', 'system'],
        'pengambil_kebijakan' => ['risk_zone', 'system'],
        'petugas_loket' => ['system'],
        'pengunjung' => ['system'],
    ];

    public static function allowedTypesForRole(?string $roleName): array
    {
        return self::ROLE_ALLOWED_TYPES[$roleName ?? ''] ?? ['system'];
    }

    public static function canViewConservationRefs(?string $roleName): bool
    {
        return in_array($roleName, [
            'administrator_sistem',
            'konservasionis_lapangan',
            'peneliti_ekologi',
            'pengambil_kebijakan',
        ], true);
    }

    public function notifyRoles(array $roles, string $type, string $message, array $refs = [], int $dedupeMinutes = 30): int
    {
        $users = User::query()
            ->where('status', 'aktif')
            ->whereHas('role', fn ($q) => $q->whereIn('name_role', $roles))
            ->pluck('user_id');

        $created = 0;
        foreach ($users as $userId) {
            $created += $this->notifyUser($userId, $type, $message, $refs, $dedupeMinutes) ? 1 : 0;
        }

        return $created;
    }

    public function notifyUser(int $userId, string $type, string $message, array $refs = [], int $dedupeMinutes = 30): bool
    {
        $roleName = User::query()
            ->where('user_id', $userId)
            ->with('role:role_id,name_role')
            ->first()
            ?->role?->name_role;

        if (! in_array($type, self::allowedTypesForRole($roleName), true)) {
            return false;
        }

        $message = mb_substr($message, 0, 500);

        if ($dedupeMinutes > 0) {
            $exists = Notification::query()
                ->where('user_id', $userId)
                ->where('notification_type', $type)
                ->where('message', $message)
                ->where('created_at', '>=', now()->subMinutes($dedupeMinutes))
                ->exists();

            if ($exists) {
                return false;
            }
        }

        $allowedRefs = self::canViewConservationRefs($roleName)
            ? array_intersect_key($refs, array_flip(['animal_id', 'zone_id', 'device_id', 'report_id', 'prediction_id']))
            : [];

        Notification::create(array_merge([
            'user_id' => $userId,
            'notification_type' => $type,
            'message' => $message,
            'is_read' => false,
            'created_at' => now(),
        ], $allowedRefs));

        return true;
    }
}
