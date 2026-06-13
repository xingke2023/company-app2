<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        $keys = auth()->user()->apiKeys()
            ->latest()
            ->get(['id', 'name', 'key', 'last_used_at', 'created_at']);

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $apiKey = ApiKey::generate(auth()->user(), $data['name']);

        return response()->json([
            'data' => [
                'id'         => $apiKey->id,
                'name'       => $apiKey->name,
                'key'        => $apiKey->key,
                'created_at' => $apiKey->created_at,
            ],
            'message' => '请保存此 Key，关闭后不再显示完整内容。',
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = auth()->user()->apiKeys()->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['message' => 'Deleted.']);
    }
}
