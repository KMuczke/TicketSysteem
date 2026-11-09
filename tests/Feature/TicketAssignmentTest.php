<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'naam' => 'Kapotte printer',
            'beschrijving' => 'De printer op de gang werkt niet.',
            'status' => 'nieuw',
        ], $attributes));
    }

    public function test_developers_can_claim_an_unassigned_ticket(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket();

        $response = $this->actingAs($developer)
            ->patch(route('tickets.assign', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success', "Ticket {$ticket->ticket_number} is aan jou toegewezen.");

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $developer->id,
            'type' => 'assigned',
            'description' => 'Ticket opgepakt',
        ]);

        $this->actingAs($developer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Aan jou toegewezen')
            ->assertSee($developer->name)
            ->assertSee('In behandeling nemen');
    }

    public function test_melders_and_beheerders_can_not_claim_a_ticket(): void
    {
        $ticket = $this->makeTicket();

        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)
                ->patch(route('tickets.assign', $ticket))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
        ]);

        $this->assertDatabaseMissing('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'assigned',
        ]);
    }

    public function test_developers_can_not_claim_a_ticket_that_is_assigned_to_someone_else(): void
    {
        $owner = User::factory()->developer()->create();
        $other = User::factory()->developer()->create();
        $ticket = $this->makeTicket(['assignee_id' => $owner->id]);

        $this->actingAs($other)
            ->patch(route('tickets.assign', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $owner->id,
        ]);

        $this->assertDatabaseMissing('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'assigned',
            'user_id' => $other->id,
        ]);
    }

    public function test_developers_can_not_claim_a_closed_ticket(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket(['status' => 'gesloten']);

        $this->actingAs($developer)
            ->patch(route('tickets.assign', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
        ]);
    }

    public function test_claiming_your_own_ticket_twice_is_idempotent(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($developer)->patch(route('tickets.assign', $ticket));
        $this->actingAs($developer)->patch(route('tickets.assign', $ticket))
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHas('success', "Ticket {$ticket->ticket_number} is aan jou toegewezen.");

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);

        $this->assertSame(
            1,
            TicketActivity::where('ticket_id', $ticket->id)
                ->where('type', 'assigned')
                ->count()
        );
    }

    public function test_a_developer_can_not_reset_the_status_of_their_ticket_by_claiming_it_again(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'in_behandeling',
        ]);

        $this->actingAs($developer)
            ->patch(route('tickets.assign', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $developer->id,
            'status' => 'in_behandeling',
        ]);

        $this->assertDatabaseMissing('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'assigned',
        ]);
    }

    public function test_developers_can_only_claim_one_active_ticket_at_a_time(): void
    {
        $developer = User::factory()->developer()->create();
        $activeTicket = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);
        $otherTicket = $this->makeTicket();

        $this->actingAs($developer)
            ->patch(route('tickets.assign', $otherTicket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $otherTicket->id,
            'assignee_id' => null,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $activeTicket->id,
            'assignee_id' => $developer->id,
        ]);
    }

    public function test_a_closed_claimed_ticket_does_not_block_claiming_a_new_one(): void
    {
        $developer = User::factory()->developer()->create();
        $closedTicket = $this->makeTicket([
            'status' => 'gesloten',
            'assignee_id' => $developer->id,
        ]);
        $newTicket = $this->makeTicket();

        $this->actingAs($developer)
            ->patch(route('tickets.assign', $newTicket))
            ->assertRedirect(route('tickets.show', $newTicket));

        $this->assertDatabaseHas('tickets', [
            'id' => $newTicket->id,
            'assignee_id' => $developer->id,
        ]);
    }

    public function test_developers_can_release_their_own_ticket(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'in_behandeling',
        ]);

        $response = $this->actingAs($developer)
            ->delete(route('tickets.release', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success', "Ticket {$ticket->ticket_number} is weer vrijgegeven.");

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => null,
            'status' => 'nieuw',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $developer->id,
            'type' => 'released',
            'description' => 'Ticket losgelaten',
        ]);

        $this->actingAs($developer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Niet toegewezen')
            ->assertSee('Oppakken');
    }

    public function test_only_the_assigned_developer_can_release_a_ticket(): void
    {
        $owner = User::factory()->developer()->create();
        $otherDeveloper = User::factory()->developer()->create();
        $ticket = $this->makeTicket(['assignee_id' => $owner->id]);

        foreach ([
            $otherDeveloper,
            User::factory()->create(),
            User::factory()->admin()->create(),
        ] as $user) {
            $this->actingAs($user)
                ->delete(route('tickets.release', $ticket))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $owner->id,
        ]);

        $this->assertDatabaseMissing('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'released',
        ]);
    }

    public function test_a_released_ticket_can_be_claimed_again(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($developer)->patch(route('tickets.assign', $ticket));
        $this->actingAs($developer)->delete(route('tickets.release', $ticket));
        $this->actingAs($developer)->patch(route('tickets.assign', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);

        $this->assertSame(
            2,
            TicketActivity::where('ticket_id', $ticket->id)
                ->where('type', 'assigned')
                ->count()
        );
    }

    public function test_the_index_shows_the_assignee_and_only_offers_oppakken_when_allowed(): void
    {
        $developer = User::factory()->developer()->create();
        $otherDeveloper = User::factory()->developer()->create();
        $melder = User::factory()->create();

        $unassigned = $this->makeTicket(['naam' => 'Nog niemand']);
        $mine = $this->makeTicket(['naam' => 'Van mij', 'assignee_id' => $developer->id, 'status' => 'gesloten']);
        $theirs = $this->makeTicket(['naam' => 'Van een ander', 'assignee_id' => $otherDeveloper->id, 'status' => 'toegewezen']);
        $closed = $this->makeTicket(['naam' => 'Gesloten', 'status' => 'gesloten']);

        $this->actingAs($developer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($otherDeveloper->name)
            ->assertSee('jij')
            ->assertSee('Niet toegewezen')
            ->assertSee('Oppakken')
            ->assertDontSee('action="'.route('tickets.assign', $mine).'"', false)
            ->assertDontSee('action="'.route('tickets.assign', $theirs).'"', false)
            ->assertDontSee('action="'.route('tickets.assign', $closed).'"', false)
            ->assertSee('action="'.route('tickets.assign', $unassigned).'"', false);

        $this->actingAs($melder)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSee('Oppakken')
            ->assertDontSee('Loslaten');
    }

    public function test_the_index_offers_loslaten_to_the_assigned_developer(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'in_behandeling',
        ]);

        $this->actingAs($developer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Loslaten')
            ->assertSee('action="'.route('tickets.release', $ticket).'"', false);
    }

    public function test_the_index_blocks_a_second_ticket_and_explains_why(): void
    {
        $developer = User::factory()->developer()->create();
        $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);
        $otherTicket = $this->makeTicket();

        $this->actingAs($developer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Je hebt al een ticket in behandeling.')
            ->assertDontSee('Oppakken')
            ->assertSee('Loslaten')
            ->assertDontSee('action="'.route('tickets.assign', $otherTicket).'"', false);
    }

    public function test_the_detail_page_only_offers_oppakken_and_release_when_allowed(): void
    {
        $unassigned = $this->makeTicket();
        $owner = User::factory()->developer()->create();
        $otherDeveloper = User::factory()->developer()->create();
        $mine = $this->makeTicket(['assignee_id' => $owner->id]);
        $theirs = $this->makeTicket(['assignee_id' => $otherDeveloper->id]);
        $closed = $this->makeTicket(['status' => 'gesloten']);

        $claimer = User::factory()->developer()->create();
        $this->actingAs($claimer)
            ->get(route('tickets.show', $unassigned))
            ->assertOk()
            ->assertSee('Oppakken')
            ->assertDontSee('Loslaten')
            ->assertSee('Niet toegewezen');

        $this->actingAs($owner)
            ->get(route('tickets.show', $mine))
            ->assertOk()
            ->assertSee('Aan jou toegewezen')
            ->assertSee('Loslaten')
            ->assertDontSee('Oppakken');

        $bystander = User::factory()->developer()->create();
        $this->actingAs($bystander)
            ->get(route('tickets.show', $theirs))
            ->assertOk()
            ->assertSee($otherDeveloper->name)
            ->assertDontSee('Oppakken')
            ->assertDontSee('Loslaten');

        $this->actingAs(User::factory()->developer()->create())
            ->get(route('tickets.show', $closed))
            ->assertOk()
            ->assertDontSee('Oppakken')
            ->assertDontSee('Loslaten');

        $this->actingAs(User::factory()->create())
            ->get(route('tickets.show', $unassigned))
            ->assertOk()
            ->assertDontSee('Oppakken')
            ->assertDontSee('Loslaten');
    }

    public function test_the_detail_page_blocks_claiming_a_second_ticket(): void
    {
        $developer = User::factory()->developer()->create();
        $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);
        $otherTicket = $this->makeTicket();

        $this->actingAs($developer)
            ->get(route('tickets.show', $otherTicket))
            ->assertOk()
            ->assertSee('Je hebt al een actief ticket opgepakt.')
            ->assertDontSee('Oppakken');
    }
}
