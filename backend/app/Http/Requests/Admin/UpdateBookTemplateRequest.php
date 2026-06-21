<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookTemplateRequest extends FormRequest
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
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_free' => 'sometimes|required|boolean',
            'template_type' => 'sometimes|required|string|in:story',
            'is_active' => 'sometimes|boolean',
            'story_goal_id' => [
                'nullable',
                'integer',
                'exists:story_goals,id',
                Rule::unique('book_templates', 'story_goal_id')->ignore($this->route('id')),
            ],
        ];
    }
}
