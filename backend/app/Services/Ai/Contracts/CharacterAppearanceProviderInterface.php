<?php

namespace App\Services\Ai\Contracts;

interface CharacterAppearanceProviderInterface
{
    public function isConfigured(): bool;

    public function describe(string $imageBinary, string $mimeType): ?string;
}
