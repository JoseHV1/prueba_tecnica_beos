<?php

namespace App\Http\Requests;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductPriceInput',
    required: ['currency_id'],
    properties: [
        new OA\Property(property: 'currency_id', description: 'UUID de una divisa existente, distinta de la divisa base del producto y sin precio registrado para este producto (no distingue mayúsculas).', type: 'string', format: 'uuid', example: '019a2c3e-6a0b-7c19-8b3d-0e1f2a3b4c5d'),
        new OA\Property(
            property: 'price',
            type: 'number',
            format: 'decimal',
            minimum: 0,
            maximum: 9999999999.99,
            nullable: true,
            example: 110.90,
            description: 'Máximo 2 decimales. Si se omite o es null, se calcula como precio_base × tasa_destino / tasa_base redondeado a 2 decimales; si el resultado supera el máximo se responde 422.'
        ),
    ]
)]
class StoreProductPriceRequest extends FormRequest
{
    private ?Currency $resolvedCurrency = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('currency_id'))) {
            $this->merge(['currency_id' => strtolower($this->input('currency_id'))]);
        }
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'currency_id' => [
                'required',
                'uuid',
                'exists:currencies,id',
                Rule::notIn([$product->currency_id]),
                Rule::unique('product_prices')->where('product_id', $product->id),
            ],
            'price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.Product::MAX_AMOUNT],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isEmpty() && $this->price() > Product::MAX_AMOUNT) {
                    $validator->errors()->add('price', 'The converted price exceeds the maximum allowed amount.');
                }
            },
        ];
    }

    public function currency(): Currency
    {
        return $this->resolvedCurrency ??= Currency::findOrFail($this->input('currency_id'));
    }

    public function price(): string
    {
        return $this->input('price') ?? $this->route('product')->priceIn($this->currency());
    }

    public function messages(): array
    {
        return [
            'currency_id.not_in' => 'The base currency of the product cannot be registered as an additional price.',
            'currency_id.unique' => 'This product already has a price in that currency.',
        ];
    }
}
