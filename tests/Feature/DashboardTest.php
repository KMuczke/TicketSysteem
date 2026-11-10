<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a ticket with sensible defaults for the dashboard tests.
     */
    private function makeTicket(User $reporter, array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'user_id' => $reporter->id,
            'naam' => 'Dashboard ticket',
            'beschrijving' => 'Beschrijving van het ticket.',
            'categorie' => 'vraag',
        ], $attributes));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_developers_see_every_ticket_with_its_status(): void
    {
        $developer = User::factory()->developer()->create();
        $melder = User::factory()->melder()->create();
        $otherMelder = User::factory()->melder()->create();

        $ownTicket = $this->makeTicket($melder, [
            'naam' => 'Eerste melding',
            'status' => 'in_behandeling',
        ]);
        $otherTicket = $this->makeTicket($otherMelder, [
            'naam' => 'Tweede melding',
            'status' => 'gesloten',
        ]);

        $this->actingAs($developer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($ownTicket->ticket_number)
            ->assertSee('Eerste melding')
            ->assertSee($otherTicket->ticket_number)
            ->assertSee('Tweede melding')
            ->assertSee('In behandeling')
            ->assertSee('Gesloten');
    }

    public function test_the_dashboard_counts_open_tickets_per_status_priority_and_developer(): void
    {
        $developerA = User::factory()->developer()->create(['name' => 'Developer A']);
        $developerB = User::factory()->developer()->create(['name' => 'Developer B']);
        $melder = User::factory()->melder()->create();

        $this->makeTicket($melder, ['status' => 'nieuw', 'prioriteit' => 'kritiek']);
        $this->makeTicket($melder, [
            'status' => 'toegewezen',
            'prioriteit' => 'hoog',
            'assignee_id' => $developerA->id,
        ]);
        $this->makeTicket($melder, [
            'status' => 'in_behandeling',
            'prioriteit' => 'hoog',
            'assignee_id' => $developerA->id,
        ]);
        $this->makeTicket($melder, [
            'status' => 'opgelost',
            'prioriteit' => 'normaal',
            'assignee_id' => $developerB->id,
        ]);
        $this->makeTicket($melder, [
            'status' => 'gesloten',
            'prioriteit' => 'laag',
            'assignee_id' => $developerB->id,
        ]);

        $response = $this->actingAs($developerA)
            ->get(route('dashboard'))
            ->assertOk();

        $status = $response->viewData('statusOverview');
        $this->assertSame(1, $status['nieuw']['count']);
        $this->assertSame(1, $status['toegewezen']['count']);
        $this->assertSame(1, $status['in_behandeling']['count']);
        $this->assertSame(0, $status['wacht_op_melder']['count']);
        $this->assertSame(1, $status['opgelost']['count']);
        $this->assertSame(0, $status['afgewezen']['count']);
        $this->assertSame(1, $status['gesloten']['count']);
        $this->assertFalse($status['gesloten']['open']);

        $priorities = $response->viewData('priorityOverview');
        $this->assertSame(1, $priorities['kritiek']['count']);
        $this->assertSame(2, $priorities['hoog']['count']);
        $this->assertSame(1, $priorities['normaal']['count']);
        // The only low-priority ticket is closed and therefore not counted.
        $this->assertSame(0, $priorities['laag']['count']);

        $developers = $response->viewData('developerOverview');
        $this->assertSame('Developer A', $developers[$developerA->id]['name']);
        $this->assertSame(2, $developers[$developerA->id]['count']);
        $this->assertSame('Developer B', $developers[$developerB->id]['name']);
        $this->assertSame(1, $developers[$developerB->id]['count']);

        $this->assertSame(5, $response->viewData('totalCount'));
        $this->assertSame(4, $response->viewData('openCount'));
        $this->assertSame(1, $response->viewData('closedCount'));
        $this->assertSame(1, $response->viewData('unassignedOpenCount'));
    }

    public function test_melders_only_see_their_own_tickets_on_the_dashboard(): void
    {
        $melder = User::factory()->melder()->create();
        $otherMelder = User::factory()->melder()->create();

        $ownTicket = $this->makeTicket($melder, ['naam' => 'Mijn eigen ticket']);
        $otherTicket = $this->makeTicket($otherMelder, ['naam' => 'Andermans ticket']);

        $response = $this->actingAs($melder)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mijn eigen ticket')
            ->assertSee($ownTicket->ticket_number)
            ->assertDontSee('Andermans ticket')
            ->assertDontSee($otherTicket->ticket_number);

        $this->assertSame(1, $response->viewData('totalCount'));
        $this->assertSame(1, $response->viewData('openCount'));
    }

    public function test_the_ticket_overview_shows_at_most_25_tickets_per_page(): void
    {
        $melder = User::factory()->melder()->create();

        for ($i = 1; $i <= 30; $i++) {
            $this->makeTicket($melder, ['naam' => 'Ticket '.$i]);
        }

        $response = $this->actingAs($melder)
            ->get(route('dashboard'))
            ->assertOk();

        $tickets = $response->viewData('tickets');

        $this->assertSame(25, $tickets->count());
        $this->assertSame(30, $tickets->total());
    }
}
