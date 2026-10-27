<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Currency',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019a2c3e-6a0b-7c19-8b3d-0e1f2a3b4c5d'),
        new OA\Property(property: 'name', type: 'string', example: 'USD'),
        new OA\Property(property: 'symbol', type: 'string', example: '$'),
        new OA\Property(property: 'exchange_rate', type: 'string', example: '1.000000', description: 'Unidades de esta divisa por 1 USD, con 6 decimales.'),
    ]
)]
class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'exchange_rate' => $this->exchange_rate,
        ];
    }
}
