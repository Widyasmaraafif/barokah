<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class PublicSettingController extends Controller
{
    public function __invoke(SettingsService $settings): JsonResponse
    {
        return response()->json($settings->allPublic());
    }
}
