<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(name="Base", description="Base API controller")
 */
abstract class ApiController extends Controller
{
    /**
     * Return a standard success JSON response.
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
            'errors'  => null,
        ], $statusCode);
    }

    /**
     * Return a standard error JSON response.
     */
    protected function errorResponse(string $message = 'Error', mixed $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode);
    }

    /**
     * Return a standardized 500 server error response.
     */
    protected function serverErrorResponse(string $message = 'An unexpected error occurred. Please try again later.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'errors'  => null,
        ], 500);
    }
}

