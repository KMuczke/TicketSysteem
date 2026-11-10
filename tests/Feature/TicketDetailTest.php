<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDetailTest extends TestCase
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

        $this->get(route('tickets.show', $ticket))->assertRedirect(route('login'));
    }

    public function test_the_detail_page_shows_all_ticket_data(): void
    {
        $reporter = User::factory()->create(['name' => 'Melder Man', 'email' => 'melder@example.com']);
        $ticket = $this->makeTicket($reporter);

        $this->actingAs($reporter)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Kapotte printer')
            ->assertSee('De printer op de gang werkt niet.')
            ->assertSee('Melder Man')
            ->assertSee('melder@example.com')
            ->assertSee('Categorie')
            ->assertSee('Prioriteit')
            ->assertSee('Vraag')
            ->assertSee('Normaal')
            ->assertSee('Aangemaakt')
            ->assertSee($ticket->created_at->format('d-m-Y H:i'))
            ->assertSee('Laatst bijgewerkt')
            ->assertSee('Nieuw');
    }

    public function test_the_detail_page_shows_comments_and_history(): void
    {
        $user = User::factory()->create(['name' => 'Melder Man']);
        $ticket = $this->makeTicket($user);

        $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => 'Ik heb hetzelfde probleem.',
        ]);

        $ticket->recordActivity(type: 'commented', description: 'Reactie geplaatst');

        $this->actingAs($user)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Ik heb hetzelfde probleem.')
            ->assertSee('Reacties')
            ->assertSee('Geschiedenis')
            ->assertSee('Ticket aangemaakt')
            ->assertSee('Reactie geplaatst');
    }

    public function test_users_can_comment_on_a_ticket(): void
    {
        $user = User::factory()->create(['name' => 'Reageerder']);
        $ticket = $this->makeTicket($user);

        $response = $this->actingAs($user)->post(route('tickets.comments.store', $ticket), [
            'body' => "Ik heb de printer gereset.\nGraag opnieuw proberen.",
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success', 'Reactie geplaatst.');

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'commented',
        ]);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Ik heb de printer gereset.')
            ->assertSee('Reageerder');
    }

    public function test_a_reply_requires_a_body(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket($user);

        $this->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('ticket_comments', 0);
    }

    public function test_only_the_author_or_an_admin_can_delete_a_comment(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket($author);
        $comment = $ticket->comments()->create([
            'user_id' => $author->id,
            'body' => 'Mijn reactie.',
        ]);

        $this->actingAs($other)
            ->delete(route('tickets.comments.destroy', [$ticket, $comment]))
            ->assertForbidden();

        $this->assertDatabaseHas('ticket_comments', ['id' => $comment->id]);

        $this->actingAs($author)
            ->delete(route('tickets.comments.destroy', [$ticket, $comment]))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseMissing('ticket_comments', ['id' => $comment->id]);

        $secondComment = $ticket->comments()->create([
            'user_id' => $author->id,
            'body' => 'Nog een reactie.',
        ]);

        $this->actingAs($admin)
            ->delete(route('tickets.comments.destroy', [$ticket, $secondComment]))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseMissing('ticket_comments', ['id' => $secondComment->id]);
    }

    public function test_history_records_created_tickets_and_updates(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['name' => 'Beheerder Bert']);
        $ticket = $this->makeTicket($user);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'created',
            'user_id' => null,
        ]);

        $this->actingAs($admin)->put(route('tickets.update', $ticket), [
            'naam' => 'Kapotte printer op de gang',
            'beschrijving' => 'De printer op de gang werkt nog steeds niet.',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'updated',
            'field' => 'naam',
            'old_value' => 'Kapotte printer',
            'new_value' => 'Kapotte printer op de gang',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('tickets.status', $ticket), [
            'status' => 'afgewezen',
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'updated',
            'field' => 'status',
            'old_value' => 'nieuw',
            'new_value' => 'afgewezen',
            'user_id' => $admin->id,
        ]);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Naam gewijzigd')
            ->assertSee('Status gewijzigd')
            ->assertSee('Afgewezen');
    }

    public function test_the_author_can_edit_their_comment(): void
    {
        $user = User::factory()->create(['name' => 'Reageerder']);
        $ticket = $this->makeTicket($user);
        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => 'Oude tekst.',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('tickets.comments.edit', [$ticket, $comment]));

        $this->actingAs($user)
            ->get(route('tickets.comments.edit', [$ticket, $comment]))
            ->assertOk()
            ->assertSee('Oude tekst.');

        $response = $this->actingAs($user)->put(route('tickets.comments.update', [$ticket, $comment]), [
            'body' => 'Nieuwe tekst.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success', 'Reactie bijgewerkt.');

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => 'Nieuwe tekst.',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'comment_updated',
            'description' => 'Reactie bijgewerkt',
        ]);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Nieuwe tekst.')
            ->assertDontSee('Oude tekst.');
    }

    public function test_only_the_author_can_edit_a_comment(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket($author);
        $comment = $ticket->comments()->create([
            'user_id' => $author->id,
            'body' => 'Van de auteur.',
        ]);

        $this->actingAs($other)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee(route('tickets.comments.edit', [$ticket, $comment]));

        $this->actingAs($other)->get(route('tickets.comments.edit', [$ticket, $comment]))->assertForbidden();
        $this->actingAs($other)->put(route('tickets.comments.update', [$ticket, $comment]), ['body' => 'Gekaapt.'])->assertForbidden();
        $this->actingAs($admin)->get(route('tickets.comments.edit', [$ticket, $comment]))->assertForbidden();
        $this->actingAs($admin)->put(route('tickets.comments.update', [$ticket, $comment]), ['body' => 'Gekaapt.'])->assertForbidden();

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => 'Van de auteur.',
        ]);
    }

    public function test_editing_a_comment_requires_a_body(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket($user);
        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => 'Blijft staan.',
        ]);

        $this->actingAs($user)
            ->put(route('tickets.comments.update', [$ticket, $comment]), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => 'Blijft staan.',
        ]);
    }

    public function test_guests_can_not_edit_a_comment(): void
    {
        $user = User::factory()->create();
        $ticket = $this->makeTicket($user);
        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => 'Openbaar.',
        ]);

        $this->get(route('tickets.comments.edit', [$ticket, $comment]))->assertRedirect(route('login'));
        $this->put(route('tickets.comments.update', [$ticket, $comment]), ['body' => 'Gekaapt.'])->assertRedirect(route('login'));
    }
}
