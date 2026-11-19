<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member> */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_anggota' => 'AWO-'.now()->year.'-'.fake()->unique()->numerify('9###'),
            'name'          => fake()->name(),
            'gender'        => fake()->randomElement(['L', 'P']),
            'phone'         => '08'.fake()->numerify('##########'),
            'joined_at'     => now()->toDateString(),
            'status'        => 'active',
        ];
    }
}
