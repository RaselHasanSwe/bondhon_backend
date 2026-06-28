<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountDisableRequestType;
use App\Http\Requests\Account\SubmitAccountDisableRequestRequest;
use App\Services\AccountDisableRequestService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AccountDisableRequestController extends ApiController
{
    /**
     * POST /api/v1/account-disable-requests
     * Submit a voluntary account disable request.
     */
    public function store(
        SubmitAccountDisableRequestRequest $request,
        AccountDisableRequestService $service,
    ): JsonResponse {
        try {
            $disableRequest = $service->submit(
                $request->user(),
                AccountDisableRequestType::from($request->validated('request_type')),
                $request->validated('message'),
            );

            return $this->successResponse(
                ['id' => $disableRequest->id],
                'Your account disable request has been submitted. Our team will review it shortly.',
                201,
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }
}
