<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:200'],
            'brand' => ['required', 'string', 'max:80'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'oldPrice' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['integer'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string'],
            'badge' => ['nullable', Rule::in(['HOT', 'NEW', 'SALE'])],
            'status' => ['required', Rule::in(['active', 'draft', 'out_of_stock'])],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ];
    }

    /**
     * Mapea los campos camelCase del frontend a snake_case de la BD.
     */
    public function toModelAttributes(): array
    {
        $data = $this->validated();
        return [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'brand' => $data['brand'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'old_price' => $data['oldPrice'] ?? null,
            'stock' => $data['stock'],
            'sizes' => $data['sizes'] ?? [],
            'colors' => $data['colors'] ?? [],
            'badge' => $data['badge'] ?? null,
            'status' => $data['status'],
            'images' => $data['images'] ?? [],
        ];
    }
}
