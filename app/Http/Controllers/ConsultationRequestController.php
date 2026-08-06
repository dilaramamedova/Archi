<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ConsultationRequestStatus;
use App\Http\Requests\StoreConsultationRequest;
use App\Models\ConsultationRequest;
use Illuminate\Http\JsonResponse;

final class ConsultationRequestController extends Controller
{
    public function store(StoreConsultationRequest $request): JsonResponse
    {
        ConsultationRequest::query()->create([
            ...$request->validated(),
            'status' => ConsultationRequestStatus::Pending,
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500)->toString(),
        ]);

        return response()->json(['message' => __('home.lead.success')], 201);
    }
}
