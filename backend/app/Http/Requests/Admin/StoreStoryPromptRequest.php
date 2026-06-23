<?php

namespace App\Http\Requests\Admin;

use App\Enums\AgeRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoryPromptRequest extends FormRequest
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
            'prompt_text' => 'required|string|max:10000',
            'language' => 'required|string|size:2|in:ru,en',
            'age_range' => ['nullable', Rule::enum(AgeRange::class)],
            'story_goal_id' => 'nullable|integer|exists:story_goals,id',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
