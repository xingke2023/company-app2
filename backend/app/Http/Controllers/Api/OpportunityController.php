<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOpportunityRequest;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Opportunity::with(['customer:id,name', 'assignedUser:id,name']);

        if ($request->has('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $opportunities = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($opportunities);
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $opportunity = Opportunity::create($request->validated());

        return response()->json($opportunity, 201);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        $opportunity->load([
            'customer:id,name,phone',
            'lead:id,name',
            'assignedUser:id,name',
            'activities' => fn ($q) => $q->latest()->limit(10),
        ]);

        return response()->json($opportunity);
    }

    public function update(StoreOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        $opportunity->update($request->validated());

        return response()->json($opportunity);
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        $opportunity->delete();

        return response()->json(null, 204);
    }
}
