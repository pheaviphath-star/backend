<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionRolesController extends Controller
{
    public function index()
    {
        $rows = DB::table('permission_roles as pr')
            ->join('roles as r', 'r.id', '=', 'pr.role_id')
            ->join('permissions as p', 'p.id', '=', 'pr.permission_id')
            ->select([
                'pr.role_id',
                'pr.permission_id',
                'r.name as role_name',
                'r.code as role_code',
                'p.name as permission_name',
                'p.group as permission_group',
                'p.web_route_key as permission_web_route_key',
                'p.is_menu_web as permission_is_menu_web',
            ])
            ->orderBy('r.name')
            ->orderBy('p.group')
            ->orderBy('p.name')
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $exists = DB::table('permission_roles')
            ->where('role_id', $validated['role_id'])
            ->where('permission_id', $validated['permission_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This permission is already assigned to the role'], 422);
        }

        DB::table('permission_roles')->insert([
            'role_id' => $validated['role_id'],
            'permission_id' => $validated['permission_id'],
        ]);

        return response()->json(['message' => 'Permission assigned to role'], 201);
    }

    public function update(Request $request, $roleId, $permissionId)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $current = DB::table('permission_roles')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->first();

        if (!$current) {
            return response()->json(['message' => 'Permission role assignment not found'], 404);
        }

        $exists = DB::table('permission_roles')
            ->where('role_id', $validated['role_id'])
            ->where('permission_id', $validated['permission_id'])
            ->exists();

        if ($exists && !($validated['role_id'] == $roleId && $validated['permission_id'] == $permissionId)) {
            return response()->json(['message' => 'This permission is already assigned to the role'], 422);
        }

        DB::transaction(function () use ($roleId, $permissionId, $validated) {
            DB::table('permission_roles')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();

            DB::table('permission_roles')->insert([
                'role_id' => $validated['role_id'],
                'permission_id' => $validated['permission_id'],
            ]);
        });

        return response()->json(['message' => 'Permission role assignment updated']);
    }

    public function destroy($roleId, $permissionId)
    {
        $deleted = DB::table('permission_roles')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Permission role assignment not found'], 404);
        }

        return response()->json(['message' => 'Permission removed from role']);
    }
}
