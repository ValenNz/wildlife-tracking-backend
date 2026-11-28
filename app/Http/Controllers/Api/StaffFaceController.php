<?php

namespace App\Http\Controllers\Api;

use App\Models\StaffFace;
use App\Models\User;
use App\Services\Ai\WildlifeVisionClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffFaceController extends ApiController
{
    public function index()
    {
        $rows = StaffFace::query()->with('user:user_id,username,full_name')->orderBy('name')->orderBy('staff_face_id')->get();

        return $this->list($rows->groupBy('name')->map(fn ($faces, $name) => [
            'name' => $name,
            'user_id' => $faces->first()->user_id,
            'user' => $faces->first()->user,
            'photo_count' => $faces->count(),
            'faces' => $faces->values(),
        ])->values());
    }

    public function store(Request $request, WildlifeVisionClient $models)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:20480',
            'name' => 'required_without:user_id|nullable|string|max:100',
            'user_id' => 'nullable|integer|exists:users,user_id',
        ]);
        if (! $models->isConfigured()) {
            return $this->error('ai-service visual belum dikonfigurasi (AI_VISION_SERVICE_URL).', 503);
        }

        $name = trim((string) ($data['name'] ?? '')) ?: User::find($data['user_id'])->full_name;
        $path = $request->file('file')->store('staff-faces', 'local');

        try {
            $face = $models->embedFace(Storage::disk('local')->path($path));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return $this->error('Wajah tidak dapat diproses: ' . $e->getMessage(), 422);
        }

        $row = StaffFace::create([
            'name' => $name,
            'user_id' => $data['user_id'] ?? null,
            'model' => mb_substr((string) ($face['face_model'] ?? 'sface'), 0, 100),
            'vector' => array_map('floatval', $face['embedding'] ?? []),
            'bbox' => $face['bbox'] ?? null,
            'detection_confidence' => $face['confidence'] ?? null,
            'file_path' => $path,
            'created_by' => $request->user()->user_id,
        ]);

        $msg = ($face['face_count'] ?? 1) > 1
            ? 'Foto disimpan. Ada beberapa wajah; wajah terbesar yang dipakai.'
            : 'Foto wajah staf disimpan.';

        return $this->success($row->load('user:user_id,username,full_name'), $msg, 201);
    }

    public function file(int $id)
    {
        $face = StaffFace::whereNotNull('file_path')->findOrFail($id);
        $disk = Storage::disk('local');

        return $disk->exists($face->file_path)
            ? $disk->response($face->file_path, basename($face->file_path), ['X-Content-Type-Options' => 'nosniff'])
            : $this->error('Berkas tidak ditemukan', 404);
    }

    public function destroy(int $id)
    {
        $face = StaffFace::findOrFail($id);
        if ($face->file_path) {
            Storage::disk('local')->delete($face->file_path);
        }
        $face->delete();

        return $this->success(null, 'Foto wajah staf dihapus');
    }

    public function destroyByName(Request $request)
    {
        $name = (string) $request->validate(['name' => 'required|string|max:100'])['name'];
        $faces = StaffFace::where('name', $name)->get();
        foreach ($faces as $face) {
            if ($face->file_path) {
                Storage::disk('local')->delete($face->file_path);
            }
            $face->delete();
        }

        return $this->success(['deleted' => $faces->count()], "Semua wajah {$name} dihapus dari galeri");
    }
}
