<?php

return [
    // 文字意图解析 — DeepSeek
    'base_url' => env('AI_BASE_URL', 'https://api.deepseek.com'),
    'api_key' => env('AI_API_KEY', ''),
    'model' => env('AI_MODEL', 'deepseek-chat'),

    // 图像识别 — 支持 vision 的模型（默认复用 DeepSeek，可覆盖为 Gemini 等）
    'vision_base_url' => env('AI_VISION_BASE_URL', env('AI_BASE_URL', 'https://api.deepseek.com')),
    'vision_api_key' => env('AI_VISION_API_KEY', env('AI_API_KEY', '')),
    'vision_model' => env('AI_VISION_MODEL', 'deepseek-chat'),

    // 语音转文字 — OpenAI Whisper 兼容接口
    'whisper_base_url' => env('AI_WHISPER_BASE_URL', ''),
    'whisper_api_key' => env('AI_WHISPER_API_KEY', env('AI_API_KEY', '')),
    'whisper_model' => env('AI_WHISPER_MODEL', 'whisper-1'),
];
