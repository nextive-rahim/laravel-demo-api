<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Models\About;

class AboutController extends Controller
{
    /**
     * Show the public "About us" content (a single record).
     */
    public function show(): AboutResource
    {
        return new AboutResource(About::singleton());
    }
}
