<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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

        $admin = User::firstOrNew(['email' => 'test@example.com']);
        $admin->name = 'Test User';
        $admin->role = UserRole::Admin;
        $admin->is_active = true;

        if (! $admin->exists) {
            $admin->password = 'password';
        }

        $admin->save();
    }
}
