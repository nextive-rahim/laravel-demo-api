<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Upload a PDF (e.g. for course content) to the uploads disk (S3) and return its URL.
     *
     * The returned `url` is meant to be placed into a course content item's
     * `payload.url`, while `path` can be stored for later deletion.
     */
    public function pdf(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
        ]);

        $disk = config('filesystems.uploads');
        $path = $request->file('file')->store('course-pdfs', $disk);

        return response()->json([
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
        ], 201);
    }

    /**
     * Upload an image (e.g. a notice banner) to the uploads disk (S3) and return its URL.
     *
     * The returned `path` is stored on the model (e.g. `notices.image_path`),
     * while `url` is the public link used to render the image.
     */
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5 MB
        ]);

        $disk = config('filesystems.uploads');
        $path = $request->file('file')->store('notice-images', $disk);

        return response()->json([
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
        ], 201);
    }
}
