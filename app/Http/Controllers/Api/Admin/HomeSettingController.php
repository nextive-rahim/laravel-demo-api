<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHomeSettingRequest;
use App\Http\Resources\HomeSettingResource;
use App\Models\HomeSetting;

class HomeSettingController extends Controller
{
    /**
     * Show the current home page settings for editing.
     */
    public function show(): HomeSettingResource
    {
        return new HomeSettingResource(HomeSetting::singleton());
    }

    /**
     * Update the single home page settings record.
     */
    public function update(UpdateHomeSettingRequest $request): HomeSettingResource
    {
        $setting = HomeSetting::singleton();
        $setting->update($request->validated());

        return new HomeSettingResource($setting);
    }
}
