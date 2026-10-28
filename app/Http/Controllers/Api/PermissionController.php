<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return PermissionResource::collection($permissions);
    }

    public function show($id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }
        return new PermissionResource($permission);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'permission_name' => 'required|string|max:100|unique:permissions,permission_name',
            'description' => 'nullable|string|max:255',
        ]);
        $permission = Permission::create($validated);
        return new PermissionResource($permission);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }
        $validated = $request->validate([
            'permission_name' => 'sometimes|string|max:100|unique:permissions,permission_name,' . $id . ',permission_id',
            'description' => 'nullable|string|max:255',
        ]);
        $permission->update($validated);
        return new PermissionResource($permission);
    }

    public function destroy($id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }
        $permission->delete();
        return response()->json(['message' => 'Permission deleted']);
    }
}
