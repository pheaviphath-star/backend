<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(Permission::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'group' => ['required', 'string', 'max:255'],
            'is_menu_web' => ['nullable', 'boolean'],
            'web_route_key' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create($validated);

        return response()->json($permission, 201);
    }

    public function show($id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        return response()->json($permission);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'group' => ['sometimes', 'required', 'string', 'max:255'],
            'is_menu_web' => ['nullable', 'boolean'],
            'web_route_key' => ['nullable', 'string', 'max:255'],
        ]);

        $permission->update([
            'name' => $validated['name'] ?? $permission->name,
            'group' => $validated['group'] ?? $permission->group,
            'is_menu_web' => array_key_exists('is_menu_web', $validated) ? $validated['is_menu_web'] : $permission->is_menu_web,
            'web_route_key' => array_key_exists('web_route_key', $validated) ? $validated['web_route_key'] : $permission->web_route_key,
        ]);

        return response()->json($permission);
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
