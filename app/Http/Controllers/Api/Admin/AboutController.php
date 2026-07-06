<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAboutRequest;
use App\Http\Resources\AboutResource;
use App\Models\About;
use Illuminate\Support\Facades\Storage;

class AboutController extends Controller
{
    /**
     * Show the current "About us" content for editing.
     */
    public function show(): AboutResource
    {
        return new AboutResource(About::singleton());
    }

    /**
     * Update the single "About us" record.
     */
    public function update(UpdateAboutRequest $request): AboutResource
    {
        $about = About::singleton();
        $data = $request->validated();

        // Remove the previous image from S3 when it is replaced or cleared.
        if (array_key_exists('image_path', $data) && $about->image_path && $data['image_path'] !== $about->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($about->image_path);
        }

        $about->update($data);

        return new AboutResource($about);
    }
}
