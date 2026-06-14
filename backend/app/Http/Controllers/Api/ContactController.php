<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreContactRequest;
use App\Http\Requests\Api\UpdateContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private ContactService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->all()));
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->service->create($request->validated());
        $contact->load('customer:id,name,company');

        return response()->json($contact, 201);
    }

    public function show(Contact $contact): JsonResponse
    {
        $contact->load('customer:id,name,company,phone,email');

        return response()->json($contact);
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        return response()->json($this->service->update($contact, $request->validated()));
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $this->service->delete($contact);

        return response()->json(null, 204);
    }
}
