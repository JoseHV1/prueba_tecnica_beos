<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Products API',
    description: "API RESTful para gestionar productos y sus precios en distintas divisas.\n\n- Todas las respuestas son JSON; los identificadores son UUID.\n- Los importes se devuelven como string con 2 decimales (`\"120.50\"`).\n- Límite de 60 peticiones por minuto por IP (cabeceras `X-RateLimit-*`); al superarlo se responde 429."
)]
#[OA\Server(url: '/api', description: 'API base')]
#[OA\Response(
    response: 'NotFound',
    description: 'Recurso no encontrado',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Resource not found.')])
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Límite de 60 peticiones por minuto superado',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Too Many Attempts.')])
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Datos inválidos',
    content: new OA\JsonContent(properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The name field is required.'),
        new OA\Property(property: 'errors', type: 'object', example: ['name' => ['The name field is required.']]),
    ])
)]
abstract class Controller {}
