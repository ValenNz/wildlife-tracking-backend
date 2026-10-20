<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 2, 'permission_id' => 1],
            ['role_id' => 3, 'permission_id' => 1],
            ['role_id' => 4, 'permission_id' => 1],
            ['role_id' => 5, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 2, 'permission_id' => 2],
            ['role_id' => 3, 'permission_id' => 2],
            ['role_id' => 1, 'permission_id' => 3],
            ['role_id' => 2, 'permission_id' => 3],
            ['role_id' => 3, 'permission_id' => 3],
            ['role_id' => 3, 'permission_id' => 4],
            ['role_id' => 3, 'permission_id' => 5],
            ['role_id' => 3, 'permission_id' => 6],
            ['role_id' => 2, 'permission_id' => 7],
            ['role_id' => 3, 'permission_id' => 7],
            ['role_id' => 4, 'permission_id' => 7],
        ];

        foreach ($rolePermissions as $rp) {
            RolePermission::query()->updateOrCreate(
                ['role_id' => $rp['role_id'], 'permission_id' => $rp['permission_id']],
                $rp,
            );
        }
    }
}