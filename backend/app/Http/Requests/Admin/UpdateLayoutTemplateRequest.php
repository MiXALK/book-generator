<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLayoutTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('layout_templates', 'key')->ignore($this->route('id')),
            ],
            'title' => 'sometimes|required|string|max:255',
            'ratio_profile' => 'sometimes|required|string|in:80_20',
            'text_position' => 'sometimes|required|string|in:top,bottom,left,right,overlay',
            'sort_order' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
