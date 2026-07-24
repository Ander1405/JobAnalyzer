<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => fn () => auth()->id() ?? User::factory(),
            'slug' => fake()->unique()->slug(2),
            'label' => $name,
            'contact' => ['name' => $name, 'email' => fake()->email()],
            'headline' => fake()->jobTitle(),
            'summary' => fake()->paragraph(),
            'experience' => [fake()->sentence()],
            'skills' => [fake()->word(), fake()->word()],
            'education' => [fake()->sentence()],
            'languages' => ['items' => ['Español nativo'], 'english_level' => null],
            'certifications' => [],
            'raw_md' => "# {$name}\n\n## Resumen\n".fake()->paragraph(),
            'source_text' => "{$name}\n".fake()->paragraph(),
            'is_active' => false,
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function withoutSourceText(): self
    {
        return $this->state(fn () => ['source_text' => null]);
    }
}
