<?php

use App\Models\GeneratedCharacter;
use App\Services\Ai\CharacterBibleComposer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generated_characters') || ! Schema::hasTable('child_profiles')) {
            return;
        }

        /** @var CharacterBibleComposer $composer */
        $composer = app(CharacterBibleComposer::class);

        GeneratedCharacter::query()
            ->with('childProfile')
            ->eachById(function (GeneratedCharacter $character) use ($composer): void {
                $profile = $character->childProfile;

                if ($profile === null) {
                    return;
                }

                $gender = $profile->child_gender
                    ?? $character->bookGenerations()->latest('id')->value('child_gender')
                    ?? 'girl';

                $styleBible = $composer->compose(
                    $profile->child_name,
                    (int) $profile->child_age,
                    $gender,
                    $character,
                    false,
                );

                if ($styleBible === trim((string) $character->style_bible)) {
                    return;
                }

                $character->forceFill(['style_bible' => $styleBible])->save();
            });
    }

    public function down(): void
    {
        //
    }
};
