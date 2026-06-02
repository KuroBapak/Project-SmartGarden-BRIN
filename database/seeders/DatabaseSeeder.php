<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Master Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'), // Or Hash::make if imported
            'role' => 'master_admin',
        ]);

        User::factory()->create([
            'name' => 'Moreno',
            'email' => 'morenokarbar@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

    }
}
