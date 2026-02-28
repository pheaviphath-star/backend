<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersRolesController extends Controller
{
    public function index()
    {
        $rows = DB::table('user_role as ur')
            ->join('users as u', 'u.id', '=', 'ur.user_id')
            ->leftJoin('profiles as prf', 'prf.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->select([
                'ur.user_id',
                'ur.role_id',
                'u.name as user_name',
                'u.email as user_email',
                'prf.image as user_image',
                'r.name as role_name',
                'r.code as role_code',
                'r.status as role_status',
            ])
            ->orderBy('u.name')
            ->orderBy('r.name')
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $exists = DB::table('user_role')
            ->where('user_id', $validated['user_id'])
            ->where('role_id', $validated['role_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This role is already assigned to the user'], 422);
        }

        DB::table('user_role')->insert([
            'user_id' => $validated['user_id'],
            'role_id' => $validated['role_id'],
        ]);

        return response()->json(['message' => 'Role assigned to user'], 201);
    }

    public function update(Request $request, $userId, $roleId)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $current = DB::table('user_role')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->first();

        if (!$current) {
            return response()->json(['message' => 'User role assignment not found'], 404);
        }

        $exists = DB::table('user_role')
            ->where('user_id', $validated['user_id'])
            ->where('role_id', $validated['role_id'])
            ->exists();

        if ($exists && !($validated['user_id'] == $userId && $validated['role_id'] == $roleId)) {
            return response()->json(['message' => 'This role is already assigned to the user'], 422);
        }

        DB::transaction(function () use ($userId, $roleId, $validated) {
            DB::table('user_role')
                ->where('user_id', $userId)
                ->where('role_id', $roleId)
                ->delete();

            DB::table('user_role')->insert([
                'user_id' => $validated['user_id'],
                'role_id' => $validated['role_id'],
            ]);
        });

        return response()->json(['message' => 'User role assignment updated']);
    }

    public function destroy($userId, $roleId)
    {
        $deleted = DB::table('user_role')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'User role assignment not found'], 404);
        }

        return response()->json(['message' => 'Role removed from user']);
    }
}
