<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity> */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(30)->setTime(8, 0);

        return [
            'code'      => 'KGT-'.now()->year.'-9'.fake()->unique()->numerify('##'),
            'title'     => 'Kegiatan '.fake()->words(2, true),
            'starts_at' => $start,
            'ends_at'   => $start->copy()->setTime(15, 0),
            'status'    => 'draft',
            'location'  => fake()->city(),
        ];
    }
}
