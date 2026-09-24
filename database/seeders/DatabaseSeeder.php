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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => env('PANEL_ADMIN_NAME', 'Admin Local'),
            'email' => env('PANEL_ADMIN_EMAIL', 'admin@example.com'),
            'password' => env('PANEL_ADMIN_PASSWORD', 'password'),
            'role' => 'super_admin',
        ]);
    }
}
