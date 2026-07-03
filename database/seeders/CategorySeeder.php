<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Matematika', 'icon' => 'calculator', 'color' => '#0ea5e9', 'description' => 'Aritmatika dan teka-teki logika.'],
            ['name' => 'Sains', 'icon' => 'beaker', 'color' => '#10b981', 'description' => 'Pengetahuan umum sains dan fakta menarik.'],
            ['name' => 'Budaya Indonesia', 'icon' => 'globe-asia-australia', 'color' => '#f59e0b', 'description' => 'Sejarah, bahasa, dan tradisi Indonesia.'],
            ['name' => 'Pop Culture', 'icon' => 'musical-note', 'color' => '#d946ef', 'description' => 'Musik, film, dan selebriti.'],
            ['name' => 'Pengetahuan Umum', 'icon' => 'academic-cap', 'color' => '#6366f1', 'description' => 'Geografi dan peristiwa terkini.'],
        ];

        foreach ($categories as $order => $category) {
            Category::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($category['name'])],
                [...$category, 'order' => $order, 'is_active' => true],
            );
        }
    }
}
