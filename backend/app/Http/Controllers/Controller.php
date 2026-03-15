<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'ZeroNoShow API',
    version: '1.0.0',
    description: 'ZeroNoShow business, booking, admin and platform management API.',
    contact: new OA\Contact(
        email: 'gerald@zeronoshow.fr',
        name: 'ZeroNoShow',
    ),
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Application server',
)]
abstract class Controller
{
    use AuthorizesRequests;
}
