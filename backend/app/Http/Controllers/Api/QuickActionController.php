<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuickAction;
use Illuminate\Http\JsonResponse;

class QuickActionController extends Controller
{
    /**
     * 小程序底部快捷按钮配置（公开接口，无需登录）。
     * 返回组装好的按钮树，结构同小程序 chat.js 原写死数组。
     */
    public function index(): JsonResponse
    {
        $actions = QuickAction::query()
            ->with('items')
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $actions->map(fn (QuickAction $a) => $a->toClientArray())->all(),
        ]);
    }
}
