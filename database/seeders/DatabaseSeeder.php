<?php

namespace Database\Seeders;

use App\Models\Ticket;
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
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $developers = User::factory(4)->create();

        $pool = $developers->pluck('id')->push($testUser->id);

        Ticket::factory(40)
            ->create([
                'developer_id' => fn () => fake()->boolean(70)
                    ? $pool->random()
                    : null,
            ]);
    }
}
