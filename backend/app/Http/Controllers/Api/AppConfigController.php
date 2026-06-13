<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    /**
     * 公开的应用配置（小程序标题等品牌文案，无需登录）。
     * 返回 { data: { key: value, ... } }
     */
    public function index(): JsonResponse
    {
        $map = AppSetting::query()
            ->orderBy('sort_order')
            ->pluck('value', 'key');

        return response()->json(['data' => $map]);
    }
}
