<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\FreeResourceResource;
use App\Models\FreeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

class FreeResourceController extends Controller
{
    /**
     * List published free resources, optionally filtered by `?type=note|pdf|book`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', new Enum(ResourceType::class)],
        ]);

        $resources = FreeResource::query()
            ->where('is_published', true)
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderBy('position')
            ->latest()
            ->get();

        return FreeResourceResource::collection($resources);
    }
}
