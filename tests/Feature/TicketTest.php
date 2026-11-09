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
        ]);

        $ticket = Ticket::firstWhere('naam', 'Printer werkt niet');

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', [
            'naam' => 'Printer werkt niet',
            'status' => 'nieuw',
        ]);
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

    public function test_beheerders_can_update_a_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $ticket = Ticket::create([
            'naam' => 'Oude naam',
            'beschrijving' => 'Beschrijving',
            'status' => 'nieuw',
        ]);

        $this->get(route('tickets.edit', $ticket))->assertOk();

        $response = $this->put(route('tickets.update', $ticket), [
            'naam' => 'Nieuwe naam',
            'beschrijving' => 'Beschrijving',
        ]);

        $response->assertRedirect(route('tickets.index'));
        $response->assertSessionHas('success', "Ticket {$ticket->ticket_number} is bijgewerkt.");
        $this->assertDatabaseHas('tickets', [
            'naam' => 'Nieuwe naam',
            'status' => 'nieuw',
        ]);

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSee("Ticket {$ticket->ticket_number} is bijgewerkt.");
    }

    public function test_beheerders_can_delete_a_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $ticket = Ticket::create([
            'naam' => 'Te verwijderen',
            'beschrijving' => 'Beschrijving',
            'status' => 'nieuw',
        ]);

        $this->delete(route('tickets.destroy', $ticket))->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_melders_and_developers_can_not_edit_or_delete_someone_elses_ticket(): void
    {
        $reporter = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $reporter->id,
            'naam' => 'Alleen de melder of beheerder',
            'beschrijving' => 'Beschrijving',
            'status' => 'nieuw',
        ]);

        foreach ([User::factory()->create(), User::factory()->developer()->create()] as $user) {
            $this->actingAs($user)->get(route('tickets.edit', $ticket))->assertForbidden();
            $this->actingAs($user)->put(route('tickets.update', $ticket), [
                'naam' => 'Gekaapt',
                'beschrijving' => 'Beschrijving',
            ])->assertForbidden();
            $this->actingAs($user)->delete(route('tickets.destroy', $ticket))->assertForbidden();
        }

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'naam' => 'Alleen de melder of beheerder',
            'status' => 'nieuw',
        ]);
    }

    public function test_users_can_edit_the_ticket_they_created(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'naam' => 'Mijn eigen ticket',
            'beschrijving' => 'Oorspronkelijke beschrijving',
            'status' => 'nieuw',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('Mijn eigen ticket');

        $response = $this->actingAs($user)->put(route('tickets.update', $ticket), [
            'naam' => 'Mijn aangepaste ticket',
            'beschrijving' => 'Nieuwe beschrijving',
        ]);

        $response->assertRedirect(route('tickets.index'));
        $response->assertSessionHas('success', "Ticket {$ticket->ticket_number} is bijgewerkt.");

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'naam' => 'Mijn aangepaste ticket',
            'status' => 'nieuw',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'updated',
            'field' => 'naam',
            'user_id' => $user->id,
        ]);
    }

    public function test_users_can_not_delete_the_ticket_they_created(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'naam' => 'Niet verwijderen',
            'beschrijving' => 'Beschrijving',
            'status' => 'nieuw',
        ]);

        $this->actingAs($user)
            ->delete(route('tickets.destroy', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_users_see_edit_for_their_own_tickets_and_only_beheerders_see_delete(): void
    {
        $reporter = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $reporter->id,
            'naam' => 'Knopcontrole',
            'beschrijving' => 'Beschrijving',
            'status' => 'nieuw',
        ]);

        $this->actingAs($reporter)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('tickets.edit', $ticket))
            ->assertDontSee('action="'.route('tickets.destroy', $ticket).'"', false);

        $this->actingAs($other)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee(route('tickets.edit', $ticket))
            ->assertDontSee('action="'.route('tickets.destroy', $ticket).'"', false);

        $this->actingAs(User::factory()->developer()->create())
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSee(route('tickets.edit', $ticket));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('tickets.edit', $ticket))
            ->assertSee('action="'.route('tickets.destroy', $ticket).'"', false);
    }

    public function test_a_ticket_gets_a_unique_number_a_creation_date_and_a_reporter(): void
    {
        $user = User::factory()->create(['name' => 'Melder Man']);
        $this->actingAs($user);

        $this->post(route('tickets.store'), [
            'naam' => 'Nieuwe melding',
            'beschrijving' => 'Iets is kapot',
        ]);

        $ticket = Ticket::firstWhere('naam', 'Nieuwe melding');

        $this->assertSame('TKT-'.str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertNotNull($ticket->created_at);
        $this->assertTrue($ticket->user->is($user));
        $this->assertSame('nieuw', $ticket->status);
    }

    public function test_a_reporter_sees_a_confirmation_with_the_ticket_number_after_creating(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('tickets.store'), [
            'naam' => 'Bevestiging test',
            'beschrijving' => 'Controle',
        ]);

        $ticket = Ticket::firstWhere('naam', 'Bevestiging test');

        $response->assertRedirect(route('tickets.index'));
        $response->assertSessionHas('success', "Ticket {$ticket->ticket_number} is aangemaakt.");

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSee("Ticket {$ticket->ticket_number} is aangemaakt.");
    }

    public function test_ticket_numbers_are_unique(): void
    {
        $first = Ticket::create(['naam' => 'Eerste', 'status' => 'nieuw']);
        $second = Ticket::create(['naam' => 'Tweede', 'status' => 'nieuw']);

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
            'status' => 'nieuw',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Melder Man')
            ->assertSee($ticket->created_at->format('d-m-Y'));
    }
}
