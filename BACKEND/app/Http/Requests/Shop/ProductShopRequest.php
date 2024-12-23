<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ProductShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale' => 'nullable|boolean', // Nếu có, phải là true hoặc false
            'search' => 'nullable|string|max:255',
            'colors' => 'nullable|array',
            'colors.*' => 'string|max:50',
            'brands' => 'nullable|array',
            'brands.*' => 'string|max:50',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|max:50',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id', // Kiểm tra id danh mục
            'sortPrice' => 'nullable|string|in:asc,desc', // Chỉ cho phép asc hoặc desc
            'minPrice' => 'nullable|numeric|min:0',
            'maxPrice' => 'nullable|numeric|min:0|gte:minPrice',
            'sortDirection' => 'nullable|string|in:asc,desc', // Chỉ cho phép asc hoặc desc
            'sortAlphaOrder' => 'nullable|in:asc,desc', // Cho phép giá trị là 'asc' hoặc 'desc'
        ];
    }
}
