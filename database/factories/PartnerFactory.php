<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Mitra '.fake()->unique()->words(2, true);

        return ['name' => $name, 'slug' => Partner::uniqueSlug($name), 'type' => 'umkm', 'status' => 'active'];
    }
}
