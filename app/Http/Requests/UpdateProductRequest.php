<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = array_map(
            fn (array $rules) => ['sometimes', ...array_diff($rules, ['required'])],
            parent::rules()
        );

        $rules['currency_id'][] = Rule::unique('product_prices')->where('product_id', $this->route('product')->id);

        return $rules;
    }

    public function messages(): array
    {
        return [
            'currency_id.unique' => 'This product already has an additional price in that currency; delete it before making it the base currency.',
        ];
    }
}
