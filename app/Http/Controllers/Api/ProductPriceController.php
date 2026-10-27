<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ProductPriceController extends Controller
{
    #[OA\Get(
        path: '/products/{id}/prices',
        summary: 'Listar los precios de un producto en otras divisas',
        tags: ['Product prices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductPrice')),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function index(Product $product): AnonymousResourceCollection
    {
        return ProductPriceResource::collection($product->prices()->with('currency')->get());
    }

    #[OA\Post(
        path: '/products/{id}/prices',
        summary: 'Registrar el precio de un producto en una divisa',
        description: 'Una divisa por producto, distinta de la divisa base. Si no se envía `price`, se calcula a partir de las tasas de cambio.',
        tags: ['Product prices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductPriceInput')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ProductPrice'),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'Otra petición simultánea registró la misma divisa'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function store(StoreProductPriceRequest $request, Product $product): JsonResponse
    {
        $price = $product->prices()->create([
            'currency_id' => $request->currency()->id,
            'price' => $request->price(),
        ]);

        return ProductPriceResource::make($price->setRelation('currency', $request->currency()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
