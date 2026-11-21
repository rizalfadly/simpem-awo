<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'AST-9'.fake()->unique()->numerify('###'),
            'name' => 'Barang '.fake()->unique()->words(2, true),
            'unit' => 'unit', 'qty_good' => 5, 'is_loanable' => true, 'status' => 'active',
        ];
    }
}
