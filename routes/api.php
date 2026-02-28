<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, RoleController, UserController , RoomController, ProfileController, GuestsController, ReservationsController, HistorysController, ReportsController, PermissionController, PermissionRolesController, UsersRolesController, BakongController};

// Auth routes
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});

Route::get('public/rooms', [RoomController::class, 'index']);
Route::post('public/guests', [GuestsController::class, 'store']);
Route::post('public/guests/login', [GuestsController::class, 'loginByEmail']);
Route::get('public/reservations/history', [ReservationsController::class, 'publicHistory']);
Route::post('public/reservations', [ReservationsController::class, 'store']);
Route::post('public/reservations/{id}/confirm-payment', [ReservationsController::class, 'confirmPayment']);
Route::post('public/reservations/{id}/cancel', [ReservationsController::class, 'cancelPublic']);
Route::post('public/bakong/khqr', [BakongController::class, 'generateKhqr']);
Route::post('public/bakong/verify', [BakongController::class, 'verifyPayment']);
Route::post('public/bakong/verify-transaction', [BakongController::class, 'verifyTransaction']);

// Protected API routes
Route::middleware('auth:api')->group(function () {
    Route::apiResource('role', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('guests', GuestsController::class);
    Route::apiResource('reservations', ReservationsController::class);
    Route::apiResource('historys', HistorysController::class);
    Route::get('permission_roles', [PermissionRolesController::class, 'index']);
    Route::post('permission_roles', [PermissionRolesController::class, 'store']);
    Route::put('permission_roles/{roleId}/{permissionId}', [PermissionRolesController::class, 'update']);
    Route::delete('permission_roles/{roleId}/{permissionId}', [PermissionRolesController::class, 'destroy']);
    Route::get('users_roles', [UsersRolesController::class, 'index']);
    Route::post('users_roles', [UsersRolesController::class, 'store']);
    Route::put('users_roles/{userId}/{roleId}', [UsersRolesController::class, 'update']);
    Route::delete('users_roles/{userId}/{roleId}', [UsersRolesController::class, 'destroy']);
    Route::post('users/{id}/profile/image', [UserController::class, 'uploadProfileImage']);
    Route::delete('users/{id}/profile/image', [UserController::class, 'removeProfileImage']);
    Route::get('reports', [ReportsController::class, 'index']);
    Route::get('/user', [ProfileController::class, 'getProfile']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/image', [ProfileController::class, 'uploadImage']);
    Route::delete('/profile/image', [ProfileController::class, 'removeImage']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
});