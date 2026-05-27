<?php

namespace Database\Seeders;

use App\Models\BookTemplate;
use App\Models\TemplateScene;
use Illuminate\Database\Seeder;

class TemplateSceneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scenesByTemplate = [
            'Маленький обмен' => [
                [
                    'scene_number' => 1,
                    'scene_instruction' => 'Introduce the child in a warm friendly opening.',
                    'image_prompt_template' => 'RU: {name} с мячом утром. EN: {name} holding a ball.',
                ],
                [
                    'scene_number' => 2,
                    'scene_instruction' => 'Add a mild conflict tied to the developmental goal.',
                    'image_prompt_template' => 'RU: Два ребенка делятся игрушкой. EN: Kids sharing a toy.',
                ],
                [
                    'scene_number' => 3,
                    'scene_instruction' => 'Show the child making a meaningful positive choice.',
                    'image_prompt_template' => 'RU: Радостная совместная игра. EN: Happy group play.',
                ],
                [
                    'scene_number' => 4,
                    'scene_instruction' => 'Close with emotional reward and confidence.',
                    'image_prompt_template' => 'RU: Семья хвалит ребенка. EN: Family praising the child.',
                ],
            ],
            'Сонный фонарик' => [
                [
                    'scene_number' => 1,
                    'scene_instruction' => 'Set a calm nighttime atmosphere.',
                    'image_prompt_template' => 'RU: Детская с мягким светом. EN: Cozy night light scene.',
                ],
                [
                    'scene_number' => 2,
                    'scene_instruction' => 'Use a gentle self-regulation step for the child.',
                    'image_prompt_template' => 'RU: Ребенок спокойно дышит. EN: Child breathing calmly.',
                ],
                [
                    'scene_number' => 3,
                    'scene_instruction' => 'Present a soothing bedtime ritual moment.',
                    'image_prompt_template' => 'RU: Спокойный ритуал сна. EN: Calm bedtime routine.',
                ],
                [
                    'scene_number' => 4,
                    'scene_instruction' => 'Finish with a proud morning reflection.',
                    'image_prompt_template' => 'RU: Утренний счастливый ребенок. EN: Proud morning smile.',
                ],
            ],
            'Смелый шаг' => [
                [
                    'scene_number' => 1,
                    'scene_instruction' => 'Start with a safe but suspenseful trigger.',
                    'image_prompt_template' => 'RU: Ребенок слышит шум в коридоре. EN: Child hearing a noise.',
                ],
                [
                    'scene_number' => 2,
                    'scene_instruction' => 'Child applies courage strategy linked to the goal.',
                    'image_prompt_template' => 'RU: Ребенок включает свет. EN: Child turns on the light.',
                ],
                [
                    'scene_number' => 3,
                    'scene_instruction' => 'Include a positive plot twist to release tension.',
                    'image_prompt_template' => 'RU: Котенок у двери. EN: Small kitten by the door.',
                ],
                [
                    'scene_number' => 4,
                    'scene_instruction' => 'End with repeatable confidence and growth.',
                    'image_prompt_template' => 'RU: Уверенный ребенок идет вперед. EN: Confident child walking.',
                ],
            ],
        ];

        foreach ($scenesByTemplate as $title => $scenes) {
            $bookTemplate = BookTemplate::query()->where('title', $title)->first();

            if (! $bookTemplate) {
                continue;
            }

            foreach ($scenes as $scene) {
                TemplateScene::query()->updateOrCreate(
                    [
                        'book_template_id' => $bookTemplate->id,
                        'scene_number' => $scene['scene_number'],
                    ],
                    [
                        'scene_instruction' => $scene['scene_instruction'],
                        'image_prompt_template' => $scene['image_prompt_template'],
                    ],
                );
            }
        }
    }
}
