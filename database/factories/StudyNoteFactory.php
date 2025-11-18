<?php

namespace Database\Factories;

use App\Models\StudyNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudyNoteFactory extends Factory
{
    protected $model = StudyNote::class;

    public function definition(): array
    {
        $sourceType = $this->faker->randomElement(['youtube','audio','text']);
        $concepts = collect(range(1, rand(3,5)))->map(function() {
            return [
                'concept' => $this->faker->words(rand(1,3), true),
                'explanation' => $this->faker->sentence(),
            ];
        })->toArray();

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(rand(3,6)),
            'source_type' => $sourceType,
            'source_reference' => $sourceType === 'youtube'
                ? 'https://www.youtube.com/watch?v=' . $this->faker->bothify('###########')
                : ($sourceType === 'audio' ? $this->faker->lexify('audio_????.mp3') : null),
            'transcript' => $this->faker->paragraphs(rand(3,5), true),
            'summary' => $this->faker->paragraph(rand(2,3)),
            'key_concepts' => $concepts,
            'study_questions' => collect(range(1, rand(5,8)))->map(fn() => $this->faker->sentence() . '?')->toArray(),
            'difficulty_level' => $this->faker->randomElement(['beginner','intermediate','advanced']),
            'estimated_study_time' => $this->faker->numberBetween(15, 120),
            'metadata' => [
                'language' => $this->faker->randomElement(['en-US','en-GB','es-ES']),
                'confidence' => $this->faker->randomFloat(2, 0.7, 0.99),
                'video_id' => $sourceType === 'youtube' ? $this->faker->bothify('###########') : null,
            ],
        ];
    }
}
