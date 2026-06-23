<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\StoryPrompt;
use App\Models\User;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use Illuminate\Support\Facades\Cache;

readonly class BookGenerationIdempotencyService
{
    public function __construct(
        private BookGenerationRepositoryInterface $bookGenerations,
    ) {}

    public function findExisting(User $user, ?string $idempotencyKey): ?BookGeneration
    {
        if ($idempotencyKey === null || $idempotencyKey === '') {
            return null;
        }

        $cachedId = Cache::get($this->redisKey($user->id, $idempotencyKey));

        if (is_int($cachedId)) {
            $generation = $this->bookGenerations->findForUserById($user->id, $cachedId);

            if ($generation !== null) {
                return $generation;
            }
        }

        return $this->bookGenerations->findByUserAndIdempotencyKey($user->id, $idempotencyKey);
    }

    public function remember(User $user, string $idempotencyKey, BookGeneration $generation): void
    {
        $ttlHours = (int) config('services.scaling.idempotency_ttl_hours', 24);
        Cache::put(
            $this->redisKey($user->id, $idempotencyKey),
            $generation->id,
            now()->addHours($ttlHours),
        );
    }

    public function computeFingerprint(
        User $user,
        BookTemplate $template,
        string $childName,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
        ?int $uploadedPhotoId,
        ?string $idempotencyKey,
    ): string {
        $payload = [
            'user_id' => $user->id,
            'template_id' => $template->id,
            'template_version' => $template->version,
            'child_name' => mb_strtolower(trim($childName)),
            'age' => $age,
            'goal' => $goal,
            'prompt_id' => $prompt?->id,
            'prompt_version' => $prompt?->version,
            'uploaded_photo_id' => $uploadedPhotoId,
            'language' => $user->language ?? 'ru',
            'idempotency_key' => $idempotencyKey,
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function redisKey(int $userId, string $idempotencyKey): string
    {
        return 'idempotency:'.$userId.':'.hash('sha256', $idempotencyKey);
    }
}
