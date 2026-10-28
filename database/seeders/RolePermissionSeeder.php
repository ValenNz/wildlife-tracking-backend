<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            [1,1],[2,1],[3,1],[4,1],[1,2],[2,2],[3,2],[1,3],[2,3],[3,3],[3,4],[3,5],[3,6],[2,7],[3,7],[4,7],
        ];
        foreach ($pairs as $pair) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $pair[0], 'permission_id' => $pair[1]],
                ['role_id' => $pair[0], 'permission_id' => $pair[1]]
            );
        }
    }
}
