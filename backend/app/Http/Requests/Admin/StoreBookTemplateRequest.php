<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookTemplateRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_free' => 'required|boolean',
            'template_type' => 'required|string|in:story',
            'is_active' => 'sometimes|boolean',
            'story_goal_id' => 'nullable|integer|exists:story_goals,id|unique:book_templates,story_goal_id',
        ];
    }
}
