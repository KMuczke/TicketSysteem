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

        $ticket = Ticket::firstWhere('naam', 'Printer werkt niet');

        $response->assertRedirect(route('tickets.show', $ticket));
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

    public function test_a_ticket_gets_a_unique_number_a_creation_date_and_a_reporter(): void
    {
        $user = User::factory()->create(['name' => 'Melder Man']);
        $this->actingAs($user);

        $this->post(route('tickets.store'), [
            'naam' => 'Nieuwe melding',
            'beschrijving' => 'Iets is kapot',
            'status' => 'open',
        ]);

        $ticket = Ticket::firstWhere('naam', 'Nieuwe melding');

        $this->assertSame('TKT-'.str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertNotNull($ticket->created_at);
        $this->assertTrue($ticket->user->is($user));
    }

    public function test_a_reporter_sees_a_confirmation_with_the_ticket_number_after_creating(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('tickets.store'), [
            'naam' => 'Bevestiging test',
            'beschrijving' => 'Controle',
            'status' => 'open',
        ]);

        $ticket = Ticket::firstWhere('naam', 'Bevestiging test');

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success', "Ticket {$ticket->ticket_number} is aangemaakt.");

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee("Ticket {$ticket->ticket_number} is aangemaakt.");
    }

    public function test_ticket_numbers_are_unique(): void
    {
        $first = Ticket::create(['naam' => 'Eerste', 'status' => 'open']);
        $second = Ticket::create(['naam' => 'Tweede', 'status' => 'open']);

        $this->assertNotNull($first->ticket_number);
        $this->assertNotNull($second->ticket_number);
        $this->assertNotSame($first->ticket_number, $second->ticket_number);
    }

    public function test_ticket_index_shows_the_number_creation_date_and_reporter(): void
    {
        $user = User::factory()->create(['name' => 'Melder Man']);
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'naam' => 'Kapotte printer',
            'beschrijving' => 'De printer werkt niet',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Melder Man')
            ->assertSee($ticket->created_at->format('d-m-Y'));
    }
}
