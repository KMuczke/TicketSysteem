<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a ticket with sensible defaults for the filter tests.
     */
    private function makeTicket(User $reporter, array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'user_id' => $reporter->id,
            'naam' => 'Filter ticket',
            'beschrijving' => 'Beschrijving van het ticket.',
            'categorie' => 'vraag',
        ], $attributes));
    }

    public function test_the_ticket_list_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();

        $new = $this->makeTicket($reporter, ['naam' => 'Nieuw ticket', 'status' => 'nieuw']);
        $busy = $this->makeTicket($reporter, ['naam' => 'Bezig ticket', 'status' => 'in_behandeling']);
        $closed = $this->makeTicket($reporter, ['naam' => 'Gesloten ticket', 'status' => 'gesloten']);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['status' => 'in_behandeling']))
            ->assertOk()
            ->assertSee($busy->ticket_number)
            ->assertDontSee($new->ticket_number)
            ->assertDontSee($closed->ticket_number);
    }

    public function test_the_ticket_list_can_be_filtered_by_priority(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();

        $critical = $this->makeTicket($reporter, ['naam' => 'Kritiek ticket', 'prioriteit' => 'kritiek']);
        $high = $this->makeTicket($reporter, ['naam' => 'Hoog ticket', 'prioriteit' => 'hoog']);
        $low = $this->makeTicket($reporter, ['naam' => 'Laag ticket', 'prioriteit' => 'laag']);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['prioriteit' => 'hoog']))
            ->assertOk()
            ->assertSee($high->ticket_number)
            ->assertDontSee($critical->ticket_number)
            ->assertDontSee($low->ticket_number);
    }

    public function test_the_ticket_list_can_be_filtered_by_category(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();

        $bug = $this->makeTicket($reporter, ['naam' => 'Bug ticket', 'categorie' => 'bug']);
        $storing = $this->makeTicket($reporter, ['naam' => 'Storing ticket', 'categorie' => 'storing']);
        $vraag = $this->makeTicket($reporter, ['naam' => 'Vraag ticket', 'categorie' => 'vraag']);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['categorie' => 'storing']))
            ->assertOk()
            ->assertSee($storing->ticket_number)
            ->assertDontSee($bug->ticket_number)
            ->assertDontSee($vraag->ticket_number);
    }

    public function test_the_ticket_list_can_be_filtered_by_application(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();

        $intranet = Application::create(['naam' => 'Intranet']);
        $portaal = Application::create(['naam' => 'Klantportaal']);

        $intranetTicket = $this->makeTicket($reporter, [
            'naam' => 'Intranet ticket',
            'application_id' => $intranet->id,
        ]);
        $portaalTicket = $this->makeTicket($reporter, [
            'naam' => 'Portaal ticket',
            'application_id' => $portaal->id,
        ]);
        $withoutApplication = $this->makeTicket($reporter, ['naam' => 'Zonder applicatie']);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['applicatie' => $intranet->id]))
            ->assertOk()
            ->assertSee($intranetTicket->ticket_number)
            ->assertDontSee($portaalTicket->ticket_number)
            ->assertDontSee($withoutApplication->ticket_number);
    }

    public function test_the_ticket_list_can_be_filtered_to_tickets_created_by_the_current_user(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->melder()->create();

        $ownTicket = $this->makeTicket($admin, ['naam' => 'Mijn eigen ticket']);
        $otherTicket = $this->makeTicket($other, ['naam' => 'Andermans ticket']);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['mijn' => 1]))
            ->assertOk()
            ->assertSee($ownTicket->ticket_number)
            ->assertDontSee($otherTicket->ticket_number);
    }

    public function test_the_ticket_list_can_be_filtered_by_assigned_developer(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();
        $developerA = User::factory()->developer()->create();
        $developerB = User::factory()->developer()->create();

        $ticketA = $this->makeTicket($reporter, [
            'naam' => 'Ticket van developer A',
            'status' => 'toegewezen',
            'assignee_id' => $developerA->id,
        ]);
        $ticketB = $this->makeTicket($reporter, [
            'naam' => 'Ticket van developer B',
            'status' => 'toegewezen',
            'assignee_id' => $developerB->id,
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['toegewezen' => (string) $developerA->id]))
            ->assertOk()
            ->assertSee($ticketA->ticket_number)
            ->assertDontSee($ticketB->ticket_number);
    }

    public function test_the_ticket_list_can_be_filtered_to_unassigned_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();
        $developer = User::factory()->developer()->create();

        $assigned = $this->makeTicket($reporter, [
            'naam' => 'Toegewezen ticket',
            'status' => 'toegewezen',
            'assignee_id' => $developer->id,
        ]);
        $unassigned = $this->makeTicket($reporter, [
            'naam' => 'Niet toegewezen ticket',
            'status' => 'nieuw',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['toegewezen' => 'geen']))
            ->assertOk()
            ->assertSee($unassigned->ticket_number)
            ->assertDontSee($assigned->ticket_number);
    }

    public function test_filters_can_be_combined(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();

        $match = $this->makeTicket($reporter, [
            'naam' => 'De juiste combinatie',
            'status' => 'in_behandeling',
            'prioriteit' => 'hoog',
            'categorie' => 'bug',
        ]);
        $wrongStatus = $this->makeTicket($reporter, [
            'naam' => 'Verkeerde status',
            'status' => 'opgelost',
            'prioriteit' => 'hoog',
            'categorie' => 'bug',
        ]);
        $wrongPriority = $this->makeTicket($reporter, [
            'naam' => 'Verkeerde prioriteit',
            'status' => 'in_behandeling',
            'prioriteit' => 'laag',
            'categorie' => 'bug',
        ]);
        $wrongCategory = $this->makeTicket($reporter, [
            'naam' => 'Verkeerde categorie',
            'status' => 'in_behandeling',
            'prioriteit' => 'hoog',
            'categorie' => 'storing',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', [
                'status' => 'in_behandeling',
                'prioriteit' => 'hoog',
                'categorie' => 'bug',
            ]))
            ->assertOk()
            ->assertSee($match->ticket_number)
            ->assertDontSee($wrongStatus->ticket_number)
            ->assertDontSee($wrongPriority->ticket_number)
            ->assertDontSee($wrongCategory->ticket_number);
    }

    public function test_melders_only_see_their_own_tickets_in_the_list(): void
    {
        $melder = User::factory()->melder()->create();
        $otherMelder = User::factory()->melder()->create();

        $ownTicket = $this->makeTicket($melder, ['naam' => 'Eigen melding']);
        $otherTicket = $this->makeTicket($otherMelder, ['naam' => 'Andere melding']);

        $this->actingAs($melder)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($ownTicket->ticket_number)
            ->assertDontSee($otherTicket->ticket_number);
    }

    public function test_filtered_pagination_links_keep_the_active_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->melder()->create();

        for ($i = 1; $i <= 26; $i++) {
            $this->makeTicket($reporter, ['naam' => 'Gesloten ticket '.$i, 'status' => 'gesloten']);
        }

        $this->actingAs($admin)
            ->get(route('tickets.index', ['status' => 'gesloten']))
            ->assertOk()
            ->assertSee('status=gesloten', false);
    }

    public function test_a_ticket_can_be_created_with_an_application(): void
    {
        $melder = User::factory()->melder()->create();
        $application = Application::create([
            'naam' => 'Intranet',
            'url' => 'https://intranet.example.com',
        ]);

        $this->actingAs($melder)
            ->post(route('tickets.store'), [
                'naam' => 'Ticket met applicatie',
                'beschrijving' => 'Beschrijving',
                'categorie' => 'vraag',
                'applicatie' => $application->id,
            ])
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'naam' => 'Ticket met applicatie',
            'application_id' => $application->id,
        ]);
    }

    public function test_an_inactive_application_can_not_be_selected_for_a_new_ticket(): void
    {
        $melder = User::factory()->melder()->create();
        $application = Application::create([
            'naam' => 'Oude applicatie',
            'is_active' => false,
        ]);

        $this->actingAs($melder)
            ->post(route('tickets.store'), [
                'naam' => 'Ticket met oude applicatie',
                'beschrijving' => 'Beschrijving',
                'categorie' => 'vraag',
                'applicatie' => $application->id,
            ])
            ->assertSessionHasErrors('applicatie');

        $this->assertDatabaseMissing('tickets', ['naam' => 'Ticket met oude applicatie']);
    }

    public function test_the_create_form_only_shows_active_applications(): void
    {
        $melder = User::factory()->melder()->create();
        Application::create(['naam' => 'Actieve applicatie']);
        Application::create(['naam' => 'Inactieve applicatie', 'is_active' => false]);

        $this->actingAs($melder)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Applicatie/website')
            ->assertSee('Actieve applicatie')
            ->assertDontSee('Inactieve applicatie');
    }

    public function test_the_application_is_shown_on_the_ticket_detail_page(): void
    {
        $melder = User::factory()->melder()->create();
        $application = Application::create([
            'naam' => 'Intranet',
            'url' => 'https://intranet.example.com',
        ]);
        $ticket = $this->makeTicket($melder, [
            'naam' => 'Ticket met applicatie',
            'application_id' => $application->id,
        ]);

        $this->actingAs($melder)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Applicatie/website')
            ->assertSee('Intranet')
            ->assertSee('https://intranet.example.com');
    }
}
