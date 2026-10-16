<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $created = fake()->dateTimeBetween('-90 days');
        $status = fake()->randomElement(['open', 'in_behandeling', 'gesloten']);

        return [
            'naam' => fake()->sentence(4),
            'beschrijving' => fake()->paragraph(),
            'status' => $status,
            'prioriteit' => fake()->randomElement(['laag', 'gemiddeld', 'hoog', 'kritiek']),
            'developer_id' => fake()->boolean(70) ? User::factory() : null,
            'gesloten_op' => $status === 'gesloten' ? fake()->dateTimeBetween($created) : null,
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }
}
