<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Album '.fake()->words(2, true), 'status' => 'draft'];
    }
}
