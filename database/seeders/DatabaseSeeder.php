<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
        ]);

        \App\Models\User::factory()->create([
            'is_admin' => false,
            'email' => 'guest@example.com',
        ]);

        Category::truncate();
        Category::create(['name' => 'Can Food']);
        Category::create(['name' => 'Dairy']);
        Category::create(['name' => 'Snacks']);
        Category::create(['name' => 'Vegetable']);
    }
}
