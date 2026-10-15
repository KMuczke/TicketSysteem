<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_ticket_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('tickets.index'))->assertOk();
    }

    public function test_authenticated_users_can_create_a_ticket(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('tickets.create'))->assertOk();

        $response = $this->post(route('tickets.store'), [
            'naam' => 'Printer werkt niet',
            'beschrijving' => 'De printer op de gang geeft een foutmelding.',
            'status' => 'open',
        ]);

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', ['naam' => 'Printer werkt niet']);
    }

    public function test_authenticated_users_can_view_a_ticket(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ticket = Ticket::create([
            'naam' => 'Wachtwoord reset',
            'beschrijving' => 'Gebruiker kan niet inloggen.',
            'status' => 'in_behandeling',
        ]);

        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('Wachtwoord reset');
    }

    public function test_authenticated_users_can_update_a_ticket(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ticket = Ticket::create([
            'naam' => 'Oude naam',
            'beschrijving' => 'Beschrijving',
            'status' => 'open',
        ]);

        $this->get(route('tickets.edit', $ticket))->assertOk();

        $response = $this->put(route('tickets.update', $ticket), [
            'naam' => 'Nieuwe naam',
            'beschrijving' => 'Beschrijving',
            'status' => 'gesloten',
        ]);

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', ['naam' => 'Nieuwe naam', 'status' => 'gesloten']);
    }

    public function test_authenticated_users_can_delete_a_ticket(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ticket = Ticket::create([
            'naam' => 'Te verwijderen',
            'beschrijving' => 'Beschrijving',
            'status' => 'open',
        ]);

        $this->delete(route('tickets.destroy', $ticket))->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }
}
