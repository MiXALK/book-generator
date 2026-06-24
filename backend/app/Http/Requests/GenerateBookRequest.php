<?php

namespace App\Http\Requests;

use App\Enums\ChildGender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'child_gender' => ['required', 'string', Rule::in(ChildGender::values())],
            'goal' => ['required', 'string', 'max:255', 'exists:story_goals,name'],
            'uploaded_photo_id' => ['nullable', 'integer', 'min:1'],
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

    public function childGender(): string
    {
        return (string) $this->validated('child_gender');
    }

    public function uploadedPhotoId(): ?int
    {
        $value = $this->validated('uploaded_photo_id');

        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    public function idempotencyKey(): ?string
    {
        $header = $this->header('Idempotency-Key');

        if (! is_string($header)) {
            return null;
        }

        $key = trim($header);

        if ($key === '' || strlen($key) > 128) {
            return null;
        }

        return $key;
    }
}
