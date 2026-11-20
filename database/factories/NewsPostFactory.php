<?php

namespace Database\Factories;

use App\Models\NewsPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsPostFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Berita '.fake()->unique()->words(3, true);

        return ['title' => $title, 'slug' => NewsPost::uniqueSlug($title), 'body' => fake()->paragraph(), 'status' => 'draft'];
    }
}
