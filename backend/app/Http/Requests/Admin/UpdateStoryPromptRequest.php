<?php

namespace App\Http\Requests\Admin;

use App\Enums\AgeRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoryPromptRequest extends FormRequest
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
            'prompt_text' => 'sometimes|required|string|max:10000',
            'language' => 'sometimes|required|string|size:2|in:ru,en',
            'age_range' => ['nullable', Rule::enum(AgeRange::class)],
            'story_goal_id' => 'nullable|integer|exists:story_goals,id',
            'is_active' => 'sometimes|boolean',
            'quality_score' => 'sometimes|numeric|min:0|max:5',
        ];
    }
}
