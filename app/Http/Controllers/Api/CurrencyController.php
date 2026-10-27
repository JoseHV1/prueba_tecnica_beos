<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class CurrencyController extends Controller
{
    #[OA\Get(
        path: '/currencies',
        summary: 'Listar divisas disponibles',
        tags: ['Currencies'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Currency')),
            ])),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        return CurrencyResource::collection(Currency::orderBy('name')->get());
    }
}
