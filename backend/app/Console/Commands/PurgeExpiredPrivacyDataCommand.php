<?php

namespace App\Console\Commands;

use App\Services\UserDataDeletionService;
use Illuminate\Console\Command;

class PurgeExpiredPrivacyDataCommand extends Command
{
    protected $signature = 'privacy:purge-expired';

    protected $description = 'Delete expired pending child photos and failed generation assets';

    public function handle(UserDataDeletionService $deletionService): int
    {
        $photosPurged = $deletionService->purgeExpiredPendingPhotos();
        $generationsPurged = $deletionService->purgeExpiredFailedGenerations();

        $this->info("Purged {$photosPurged} pending photo(s) and {$generationsPurged} failed generation(s).");

        return self::SUCCESS;
    }
}
