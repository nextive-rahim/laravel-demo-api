<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * List all registered users for the admin dashboard.
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::query()->latest()->get());
    }

    /**
     * Aggregate counts shown on the admin dashboard.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_users' => User::query()->count(),
            'admins' => User::query()->where('is_admin', true)->count(),
            'customers' => User::query()->where('is_admin', false)->count(),
        ]);
    }
}
