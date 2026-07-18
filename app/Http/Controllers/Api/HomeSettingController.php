<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeSettingResource;
use App\Models\HomeSetting;
use Illuminate\Support\Facades\Cache;

class HomeSettingController extends Controller
{
    /**
     * Show the public home page settings (hero + stats). Served from cache to
     * avoid a database round-trip on every visitor; admin edits bust it via the
     * model's saved event.
     */
    public function show(): HomeSettingResource
    {
        $setting = Cache::remember(
            HomeSetting::PUBLIC_CACHE_KEY,
            now()->addDay(),
            fn (): HomeSetting => HomeSetting::singleton(),
        );

        return new HomeSettingResource($setting);
    }
}
