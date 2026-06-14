<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssistantNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AssistantNote::query()->orderByDesc('created_at');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'    => 'required|in:note,plan,ai_suggestion',
            'content' => 'required|string|max:2000',
        ]);

        $note = AssistantNote::create($data);

        return response()->json($note, 201);
    }

    public function destroy(AssistantNote $note): JsonResponse
    {
        $note->delete();

        return response()->json(null, 204);
    }
}
