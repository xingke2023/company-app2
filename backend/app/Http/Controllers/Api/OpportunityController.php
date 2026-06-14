<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOpportunityRequest;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(private OpportunityService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->all()));
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        $opportunity->load([
            'customer:id,name,phone',
            'lead:id,name',
            'activities' => fn ($q) => $q->latest()->limit(10),
        ]);

        return response()->json($opportunity);
    }

    public function update(Request $request, Opportunity $opportunity): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'in:prospect,proposal,negotiation,won,lost'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json($this->service->update($opportunity, $data));
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        $this->service->delete($opportunity);

        return response()->json(null, 204);
    }
}
