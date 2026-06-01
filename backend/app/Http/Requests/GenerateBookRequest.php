<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'child_name' => ['required', 'string', 'max:120'],
            'age' => ['required', 'integer', 'min:2', 'max:12'],
            'goal' => ['required', 'string', 'max:255'],
            'book_template_id' => ['required', 'integer', 'exists:book_templates,id'],
        ];
    }

    public function childName(): string
    {
        return (string) $this->validated('child_name');
    }

    public function age(): int
    {
        return (int) $this->validated('age');
    }

    public function goal(): string
    {
        return (string) $this->validated('goal');
    }

    public function bookTemplateId(): int
    {
        return (int) $this->validated('book_template_id');
    }
}
