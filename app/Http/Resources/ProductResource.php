<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019a2c3e-7b1f-7d2a-9c4e-1f2a3b4c5d6e'),
        new OA\Property(property: 'name', type: 'string', example: 'Teclado mecánico'),
        new OA\Property(property: 'description', type: 'string', example: 'Teclado mecánico con switches rojos'),
        new OA\Property(property: 'price', type: 'string', example: '120.50'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/Currency'),
        new OA\Property(property: 'tax_cost', type: 'string', example: '19.28'),
        new OA\Property(property: 'manufacturing_cost', type: 'string', example: '45.00'),
        new OA\Property(property: 'prices', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductPrice'), description: 'Solo en GET /products/{id}. Precios en divisas distintas a la base.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'tax_cost' => $this->tax_cost,
            'manufacturing_cost' => $this->manufacturing_cost,
            'prices' => ProductPriceResource::collection($this->whenLoaded('prices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
