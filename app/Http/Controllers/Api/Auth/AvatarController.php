<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    /**
     * Upload (or replace) the authenticated user's avatar image on the uploads disk (S3).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5 MB
        ]);

        $user = $request->user();
        $disk = config('filesystems.uploads');

        // Remove the previous avatar so we don't orphan files in the bucket.
        if ($user->avatar_path) {
            Storage::disk($disk)->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', $disk);

        $user->update(['avatar_path' => $path]);

        return response()->json(['user' => new UserResource($user)]);
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk(config('filesystems.uploads'))->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return response()->json(['user' => new UserResource($user)]);
    }
}
