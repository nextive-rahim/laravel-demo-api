<?php

namespace App\Http\Controllers\Api;

use App\Enums\AdPlacement;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvertisementResource;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

class AdvertisementController extends Controller
{
    /**
     * List the live advertisements for the public website.
     *
     * Optionally filter by `?placement=banner|popup|home`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'placement' => ['nullable', new Enum(AdPlacement::class)],
        ]);

        $ads = Advertisement::query()
            ->active()
            ->when($validated['placement'] ?? null, fn ($query, $placement) => $query->where('placement', $placement))
            ->orderBy('position')
            ->latest()
            ->get();

        return AdvertisementResource::collection($ads);
    }
}
