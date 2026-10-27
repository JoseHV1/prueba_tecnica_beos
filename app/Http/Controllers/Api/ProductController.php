<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/products',
        summary: 'Listar productos (paginado)',
        description: 'Ordenados del más reciente al más antiguo. Cada producto incluye su divisa base.',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Filtra por nombre (contiene). `%` y `_` se buscan como texto.', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                new OA\Property(property: 'links', type: 'object'),
                new OA\Property(property: 'meta', type: 'object'),
            ])),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $products = Product::with('currency')
            ->search($request->validated('search'))
            ->latest('id')
            ->paginate($request->validated('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    #[OA\Post(
        path: '/products',
        summary: 'Crear un producto',
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return ProductResource::make($product->load('currency'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/products/{id}',
        summary: 'Obtener un producto con sus precios',
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load(['currency', 'prices.currency']));
    }

    #[OA\Put(
        path: '/products/{id}',
        summary: 'Actualizar un producto',
        description: 'Actualización parcial: solo se modifican los campos enviados. `currency_id` no puede ser una divisa en la que el producto ya tenga un precio adicional (422).',
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductUpdateInput')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    #[OA\Patch(
        path: '/products/{id}',
        summary: 'Actualizar un producto (equivalente a PUT)',
        description: 'Actualización parcial: solo se modifican los campos enviados. `currency_id` no puede ser una divisa en la que el producto ya tenga un precio adicional (422).',
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductUpdateInput')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return ProductResource::make($product->load('currency'));
    }

    #[OA\Delete(
        path: '/products/{id}',
        summary: 'Eliminar un producto y sus precios',
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 204, description: 'Eliminado'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ]
    )]
    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
