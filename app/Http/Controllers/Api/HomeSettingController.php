<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeSettingResource;
use App\Models\HomeSetting;

class HomeSettingController extends Controller
{
    /**
     * Show the public home page settings (hero + stats).
     */
    public function show(): HomeSettingResource
    {
        return new HomeSettingResource(HomeSetting::singleton());
    }
}
