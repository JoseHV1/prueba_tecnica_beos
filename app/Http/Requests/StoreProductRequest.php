<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductUpdateInput',
    description: 'Todos los campos son opcionales: solo se actualizan los enviados.',
    properties: [
        new OA\Property(property: 'name', description: 'Único entre todos los productos (no distingue mayúsculas).', type: 'string', maxLength: 255, example: 'Teclado mecánico'),
        new OA\Property(property: 'description', type: 'string', maxLength: 2000, example: 'Teclado mecánico con switches rojos'),
        new OA\Property(property: 'price', description: 'Máximo 2 decimales. También se acepta como string numérico.', type: 'number', format: 'decimal', minimum: 0, maximum: 9999999999.99, example: 120.50),
        new OA\Property(property: 'currency_id', description: 'UUID de una divisa existente (no distingue mayúsculas).', type: 'string', format: 'uuid', example: '019a2c3e-6a0b-7c19-8b3d-0e1f2a3b4c5d'),
        new OA\Property(property: 'tax_cost', description: 'Máximo 2 decimales. También se acepta como string numérico.', type: 'number', format: 'decimal', minimum: 0, maximum: 9999999999.99, example: 19.28),
        new OA\Property(property: 'manufacturing_cost', description: 'Máximo 2 decimales. También se acepta como string numérico.', type: 'number', format: 'decimal', minimum: 0, maximum: 9999999999.99, example: 45),
    ]
)]
#[OA\Schema(
    schema: 'ProductInput',
    required: ['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost'],
    allOf: [new OA\Schema(ref: '#/components/schemas/ProductUpdateInput')]
)]
class StoreProductRequest extends FormRequest
{
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
        $money = ['numeric', 'decimal:0,2', 'min:0', 'max:'.Product::MAX_AMOUNT];

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Product::class, 'name')],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', ...$money],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'tax_cost' => ['required', ...$money],
            'manufacturing_cost' => ['required', ...$money],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A product with this name already exists.',
        ];
    }
}
