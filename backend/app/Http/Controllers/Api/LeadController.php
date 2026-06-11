<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with('assignedUser:id,name');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('company', 'like', '%'.$request->search.'%');
            });
        }

        $leads = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($leads);
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = Lead::create($request->validated());

        return response()->json($lead, 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['assignedUser:id,name', 'opportunities', 'activities' => fn ($q) => $q->latest()->limit(10)]);

        return response()->json($lead);
    }

    public function update(StoreLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead->update($request->validated());

        return response()->json($lead);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(null, 204);
    }
}
