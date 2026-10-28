<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,role_id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,permission_id',
        ]);
        DB::table('role_permissions')->where('role_id', $validated['role_id'])->delete();
        foreach ($validated['permission_ids'] as $pid) {
            DB::table('role_permissions')->insert([
                'role_id' => $validated['role_id'],
                'permission_id' => $pid,
            ]);
        }
        return response()->json(['message' => 'Role permissions synced']);
    }
}
