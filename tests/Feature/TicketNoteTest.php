<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketNoteTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(User $reporter): Ticket
    {
        return Ticket::create([
            'user_id' => $reporter->id,
            'naam' => 'Kapotte printer',
            'beschrijving' => 'De printer op de gang werkt niet.',
            'status' => 'nieuw',
        ]);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $ticket = $this->makeTicket(User::factory()->create());

        $this->post(route('tickets.notes.store', $ticket), [
            'note' => 'Geheim',
        ])->assertRedirect(route('login'));
    }

    public function test_a_user_can_save_a_private_note(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket($user);

        $response = $this->actingAs($user)->post(route('tickets.notes.store', $ticket), [
            'note' => 'Interne notitie: klant belt morgen terug.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success', 'Notitie opgeslagen. Alleen jij kunt deze zien.');

        $this->assertDatabaseHas('ticket_notes', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Interne notitie: klant belt morgen terug.',
        ]);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Notities')
            ->assertSee('Interne notitie: klant belt morgen terug.');
    }

    public function test_a_note_is_only_visible_to_its_author(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket($author);

        $this->actingAs($author)->post(route('tickets.notes.store', $ticket), [
            'note' => 'Alleen voor mij bedoeld.',
        ]);

        $this->actingAs($other)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Alleen voor mij bedoeld.');

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Alleen voor mij bedoeld.');
    }

    public function test_each_user_only_sees_their_own_notes(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $ticket = $this->makeTicket($first);

        $this->actingAs($first)->post(route('tickets.notes.store', $ticket), [
            'note' => 'Notitie van de eerste gebruiker.',
        ]);

        $this->actingAs($second)->post(route('tickets.notes.store', $ticket), [
            'note' => 'Notitie van de tweede gebruiker.',
        ]);

        $this->actingAs($first)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Notitie van de eerste gebruiker.')
            ->assertDontSee('Notitie van de tweede gebruiker.');

        $this->actingAs($second)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Notitie van de tweede gebruiker.')
            ->assertDontSee('Notitie van de eerste gebruiker.');
    }

    public function test_only_the_author_can_delete_a_note(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket($author);

        $this->actingAs($author)->post(route('tickets.notes.store', $ticket), [
            'note' => 'Te verwijderen notitie.',
        ]);

        $note = $ticket->notes()->firstOrFail();

        $this->actingAs($other)
            ->delete(route('tickets.notes.destroy', [$ticket, $note]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('tickets.notes.destroy', [$ticket, $note]))
            ->assertForbidden();

        $this->assertDatabaseHas('ticket_notes', ['id' => $note->id]);

        $this->actingAs($author)
            ->delete(route('tickets.notes.destroy', [$ticket, $note]))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseMissing('ticket_notes', ['id' => $note->id]);
    }

    public function test_notes_are_not_visible_in_the_public_history(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)->post(route('tickets.notes.store', $ticket), [
            'note' => 'Geheime notitie-inhoud.',
        ]);

        $this->assertDatabaseCount('ticket_activities', 1);
        $this->assertDatabaseMissing('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'note',
        ]);
    }

    public function test_a_note_requires_a_body(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)
            ->post(route('tickets.notes.store', $ticket), ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('ticket_notes', 0);
    }
}
