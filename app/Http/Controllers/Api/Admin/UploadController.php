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
}
