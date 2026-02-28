<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\CloudinaryService;

class UserController extends Controller
{
    public function index()
    {
        $Users = User::with('profile')->get();
        return response()->json($Users);
    }

    public function store(Request $request)
    {
        $User = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);
        $User->save();

        $User->profile()->updateOrCreate([], [
            'phone' => null,
            'address' => null,
            'image' => null,
            'type' => null,
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $User->load('profile')], 201);
    }

    public function show($id)
    {
        $User = User::find($id);
        if (!$User) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($User->load('profile'));
    }

    public function update(Request $request, $id)
    {
        $User = User::find($id);
        if (!$User) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $User->name = $request->input('name', $User->name);
        $User->email = $request->input('email', $User->email);
        if ($request->has('password')) {
            $User->password = bcrypt($request->input('password'));
        }
        $User->save();

        return response()->json(['message' => 'User updated successfully', 'user' => $User->load('profile')]);
    }

    public function uploadProfileImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $User = User::with('profile')->find($id);
        if (!$User) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $cloudinary = app(CloudinaryService::class);

        if ($User->profile && $User->profile->type) {
            $cloudinary->deleteImage($User->profile->type);
        }

        $upload = $cloudinary->uploadImage($request->file('image')->getRealPath(), 'profiles');

        $User->profile()->updateOrCreate([], [
            'image' => $upload['url'],
            'type' => $upload['public_id'],
        ]);

        return response()->json([
            'message' => 'Profile image updated successfully',
            'user' => $User->load('profile')
        ]);
    }

    public function removeProfileImage($id)
    {
        $User = User::with('profile')->find($id);
        if (!$User) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($User->profile && $User->profile->type) {
            $cloudinary = app(CloudinaryService::class);
            $cloudinary->deleteImage($User->profile->type);
            $User->profile->image = null;
            $User->profile->type = null;
            $User->profile->save();
        }

        return response()->json([
            'message' => 'Profile image removed successfully',
            'user' => $User->load('profile')
        ]);
    }

    public function destroy($id)
    {
        $User = User::find($id);
        if (!$User) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $User->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
