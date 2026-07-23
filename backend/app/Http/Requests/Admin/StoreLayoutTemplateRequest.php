<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLayoutTemplateRequest extends FormRequest
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
            'key' => 'required|string|max:100|unique:layout_templates,key',
            'title' => 'required|string|max:255',
            'ratio_profile' => 'required|string|in:80_20',
            'text_position' => 'required|string|in:top,bottom,left,right,overlay',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
