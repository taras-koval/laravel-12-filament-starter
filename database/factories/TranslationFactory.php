<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $groups = ['general', 'auth', 'validation', 'navigation', 'forms'];

        return [
            'key' => $this->faker->words(2, true),
            'group' => $this->faker->randomElement($groups),
            'values' => [
                'en' => $this->faker->sentence(),
                'uk' => 'Український переклад для ' . $this->faker->word(),
                'pl' => 'Polskie tłumaczenie dla ' . $this->faker->word(),
            ],
        ];
    }

    public function withSpecificKey(string $key): static
    {
        return $this->state([
            'key' => $key,
        ]);
    }

    public function withGroup(string $group): static
    {
        return $this->state([
            'group' => $group,
        ]);
    }

    public function withValues(array $values): static
    {
        return $this->state([
            'values' => $values,
        ]);
    }

    public function missingLocale(string $locale): static
    {
        return $this->state(function (array $attributes) use ($locale) {
            $values = $attributes['values'];
            unset($values[$locale]);
            return ['values' => $values];
        });
    }
}
