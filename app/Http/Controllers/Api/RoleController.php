<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\Rule;

class RoleController extends ApiController
{
    public function index(Request $request)
    {
        $query = Role::query()->withCount('users')->with('permissions');

        if ($request->filled('search')) {
            $query->where('name_role', 'like', '%' . $request->search . '%');
        }

        $this->applySort($query, $request, ['role_id', 'name_role', 'created_at'], 'role_id', 'asc');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(Role::with('permissions')->withCount('users')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $this->aliasName($request);
        $data = $request->validate([
            'name_role' => 'required|string|max:50|alpha_dash|unique:roles,name_role',
            'description' => 'nullable|string|max:255',
            'permission_ids' => 'sometimes|array',
            'permission_ids.*' => 'integer|exists:permissions,permission_id',
        ]);

        $role = DB::transaction(function () use ($data) {
            $role = Role::create($data);
            $role->permissions()->sync($data['permission_ids'] ?? []);

            return $role;
        });

        return $this->success($role->load('permissions'), 'Role berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $this->aliasName($request);
        $data = $request->validate([
            'name_role' => ['sometimes', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'name_role')->ignore($role->role_id, 'role_id')],
            'description' => 'nullable|string|max:255',
        ]);

        if (isset($data['name_role']) && $data['name_role'] !== $role->name_role && self::isSystemRole($role)) {
            return $this->error('Nama role bawaan sistem tidak dapat diubah (dipakai untuk target notifikasi).', 422);
        }

        $role->update($data);

        return $this->success($role->fresh('permissions'), 'Role diperbarui');
    }

    public function destroy(int $id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if (self::isSystemRole($role)) {
            return $this->error('Role bawaan sistem tidak dapat dihapus.', 409);
        }
        if ($role->users_count > 0) {
            return $this->error("Role masih dipakai oleh {$role->users_count} user.", 409);
        }

        $role->delete();

        return $this->success(null, 'Role dihapus');
    }

    public function permissions(int $id)
    {
        return $this->list(Role::findOrFail($id)->permissions);
    }

    public function syncPermissions(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $request->merge(['permission_ids' => $request->input('permission_ids', $request->input('permissions', []))]);
        $data = $request->validate([
            'permission_ids' => 'present|array',
            'permission_ids.*' => 'integer|exists:permissions,permission_id',
        ]);

        $manageRolesId = Permission::where('name_permission', 'manage_roles')->value('permission_id');
        if ($this->isOwnRole($request, $role) && ! in_array((int) $manageRolesId, array_map('intval', $data['permission_ids']), true)) {
            return $this->error('Anda tidak dapat mencabut manage_roles dari role Anda sendiri.', 422);
        }

        DB::transaction(fn () => $role->permissions()->sync($data['permission_ids']));

        return $this->success($role->fresh('permissions'), 'Permission role disinkronkan');
    }

    public function assignPermission(int $id, int $permissionId)
    {
        $role = Role::findOrFail($id);
        Permission::findOrFail($permissionId);
        $role->permissions()->syncWithoutDetaching([$permissionId]);

        return $this->success($role->fresh('permissions'), 'Permission ditambahkan ke role');
    }

    public function revokePermission(Request $request, int $id, int $permissionId)
    {
        $role = Role::findOrFail($id);

        if ($this->isOwnRole($request, $role)
            && Permission::where('permission_id', $permissionId)->value('name_permission') === 'manage_roles') {
            return $this->error('Anda tidak dapat mencabut manage_roles dari role Anda sendiri.', 422);
        }
        $role->permissions()->detach($permissionId);

        return $this->success($role->fresh('permissions'), 'Permission dicabut dari role');
    }

    private static function isSystemRole(Role $role): bool
    {
        return array_key_exists($role->name_role, RolePermissionSeeder::ROLES);
    }

    private function isOwnRole(Request $request, Role $role): bool
    {
        return (int) $request->user()->role_id === (int) $role->role_id;
    }

    private function aliasName(Request $request): void
    {
        if (! $request->has('name_role') && $request->has('role_name')) {
            $request->merge(['name_role' => $request->input('role_name')]);
        }
    }
}
