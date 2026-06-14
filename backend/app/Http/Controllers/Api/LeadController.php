<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(private LeadService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->all()));
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['opportunities', 'activities' => fn ($q) => $q->latest()->limit(10)]);

        return response()->json($lead);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'in:web,referral,call,exhibition,other'],
            'status' => ['nullable', 'in:new,contacted,qualified,converted,lost'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json($this->service->update($lead, $data));
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $this->service->delete($lead);

        return response()->json(null, 204);
    }
}
