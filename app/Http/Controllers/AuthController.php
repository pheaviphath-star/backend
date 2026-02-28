<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWT;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => "required|string",
            'email' => "required|string|email|unique:users,email",
            'password' => "required|string|min:6,confirmed",
            'phone' => "nullable|string",
            'address' => "nullable|string",
            'image' => "nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
            'type' => "nullable|string",
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profiles', 'public');
        }

        $user->profile()->create([
            'phone' => $request->phone,
            'address' => $request->address,
            'image' => $imagePath,
            'type' => $request->type,
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $user->load('profile')], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            if (! $token = JWTAuth::attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
        
        $roleIds = DB::select("
            SELECT r.*
            FROM roles r
            INNER JOIN user_role ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ", [JWTAuth::user()->id]);
        
        $permissions = DB::select("
            SELECT p.*
            FROM permissions p
            INNER JOIN permission_roles pr ON p.id = pr.permission_id
            INNER JOIN roles r ON pr.role_id = r.id
            INNER JOIN user_role ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ", [JWTAuth::user()->id]);
        return response()->json(['token' => $token, 'user' => Auth::user()->load('profile'), 'permissions'=> $permissions, 'roles' => $roleIds]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'User logged out successfully']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not log out user'], 500);
        }
    }
}
