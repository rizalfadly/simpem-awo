<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArchiveDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Dokumen '.fake()->words(2, true), 'category' => 'notulen', 'access' => 'members',
            'file_path' => 'archive/test.pdf', 'original_name' => 'test.pdf', 'mime' => 'application/pdf', 'size_bytes' => 100,
        ];
    }
}
