<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreActivityRequest;
use App\Http\Requests\Api\UpdateActivityRequest;
use App\Models\Activity;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(private ActivityService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['customer_id', 'lead_id', 'opportunity_id', 'type', 'completed', 'per_page']);

        return response()->json($this->service->list($filters));
    }

    public function store(StoreActivityRequest $request): JsonResponse
    {
        $activity = $this->service->create($request->validated());
        $activity->load(['customer:id,name', 'lead:id,name', 'opportunity:id,title', 'user:id,name']);

        return response()->json($activity, 201);
    }

    public function show(Activity $activity): JsonResponse
    {
        $activity->load([
            'customer:id,name,company,phone',
            'lead:id,name,company,phone',
            'opportunity:id,title,stage,amount',
            'user:id,name',
        ]);

        return response()->json($activity);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        return response()->json($this->service->update($activity, $request->validated()));
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $this->service->delete($activity);

        return response()->json(null, 204);
    }
}
