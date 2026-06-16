<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Data\IllustrationGenerationInput;

interface IllustrationGenerationProviderInterface
{
    public function isConfigured(): bool;

    public function generateIllustration(IllustrationGenerationInput $input): ?string;
}
