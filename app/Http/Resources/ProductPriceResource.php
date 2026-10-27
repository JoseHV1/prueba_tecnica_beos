<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductPrice',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019a2c3e-8c2d-7e3f-a4b5-c6d7e8f90a1b'),
        new OA\Property(property: 'product_id', type: 'string', format: 'uuid', example: '019a2c3e-7b1f-7d2a-9c4e-1f2a3b4c5d6e'),
        new OA\Property(property: 'price', type: 'string', example: '110.90'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/Currency'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'price' => $this->price,
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
