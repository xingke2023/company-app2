<?php

return [
    // 文字意图解析 — DeepSeek（与 paper 同一提供商）
    'base_url' => env('AI_BASE_URL', 'https://api.deepseek.com'),
    'api_key' => env('AI_API_KEY', ''),
    'model' => env('AI_MODEL', 'deepseek-chat'),
];
