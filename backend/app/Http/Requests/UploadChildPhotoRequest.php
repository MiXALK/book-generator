<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadChildPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'max:5120'],
            'parental_consent' => ['required', 'accepted'],
            'child_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function parentalConsent(): bool
    {
        return (bool) $this->validated('parental_consent');
    }

    public function childName(): ?string
    {
        $name = $this->validated('child_name');

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return trim($name);
    }
}
