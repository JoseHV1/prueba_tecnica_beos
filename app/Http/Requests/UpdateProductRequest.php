<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = array_map(
            fn (array $rules) => ['sometimes', ...array_diff($rules, ['required'])],
            parent::rules()
        );

        $rules['name'] = ['sometimes', 'string', 'max:255', Rule::unique(Product::class, 'name')->ignore($this->route('product'))];
        $rules['currency_id'][] = Rule::unique('product_prices')->where('product_id', $this->route('product')->id);

        return $rules;
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'currency_id.unique' => 'This product already has an additional price in that currency; delete it before making it the base currency.',
        ];
    }
}
