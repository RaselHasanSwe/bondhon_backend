<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'MyBouma Matrimony API',
    description: 'Full-stack matrimony platform API. All protected endpoints require Bearer token from Sanctum.',
    contact: new OA\Contact(email: 'admin@mybouma.com'),
    license: new OA\License(name: 'MIT')
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Primary API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Laravel Sanctum token. Obtain via POST /api/v1/auth/login'
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object'),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', nullable: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors', type: 'object', nullable: true),
    ]
)]
class OpenApiSpec
{
    // This class exists solely to hold project-level OpenAPI annotations.
}
