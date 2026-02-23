<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->roleIsAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'min:2'],
            'description' => ['nullable'],
            'category_id' => ['exists:categories,id'],
            'images' => ['nullable', 'array', 'max:4'],
            'images[*]' => ['image'],
            'slug' => ['required', 'unique:products,slug']
        ];
    }

    public function prepareForValidation()
    {
        if ($this->slug) {
            $slug = uuid_create() . $this->slug;
            $this->merge(["slug" => $slug]);
        }
    }
}
