<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'naam' => 'Status test',
            'beschrijving' => 'De status van dit ticket wordt getest.',
            'status' => 'nieuw',
        ], $attributes));
    }

    public function test_the_enum_contains_all_seven_lifecycle_statuses(): void
    {
        $this->assertSame([
            'nieuw' => 'Nieuw',
            'toegewezen' => 'Toegewezen',
            'in_behandeling' => 'In behandeling',
            'wacht_op_melder' => 'Wacht op melder',
            'opgelost' => 'Opgelost',
            'afgewezen' => 'Afgewezen',
            'gesloten' => 'Gesloten',
        ], TicketStatus::options());

        $this->assertCount(7, TicketStatus::cases());
        $this->assertTrue(TicketStatus::Gesloten->isFinal());
        $this->assertFalse(TicketStatus::Nieuw->isFinal());
    }

    public function test_a_new_ticket_always_starts_as_nieuw(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'naam' => 'Ticket zonder status',
            'beschrijving' => 'Beschrijving',
        ]);

        $this->assertSame('nieuw', $ticket->status);
    }

    public function test_the_status_can_not_be_changed_through_the_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)->put(route('tickets.update', $ticket), [
            'naam' => 'Aangepaste naam',
            'beschrijving' => 'Aangepaste beschrijving',
            'status' => 'gesloten',
        ])->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'naam' => 'Aangepaste naam',
            'status' => 'nieuw',
        ]);
    }

    public function test_all_allowed_transitions_can_be_performed_by_a_beheerder(): void
    {
        $admin = User::factory()->admin()->create();

        $transitions = [
            ['nieuw', 'afgewezen'],
            ['toegewezen', 'in_behandeling'],
            ['in_behandeling', 'wacht_op_melder'],
            ['in_behandeling', 'opgelost'],
            ['wacht_op_melder', 'in_behandeling'],
            ['opgelost', 'gesloten'],
            ['opgelost', 'in_behandeling'],
            ['afgewezen', 'gesloten'],
        ];

        foreach ($transitions as [$from, $to]) {
            $ticket = $this->makeTicket(['status' => $from]);

            $this->actingAs($admin)
                ->patch(route('tickets.status', $ticket), ['status' => $to])
                ->assertRedirect(route('tickets.show', $ticket));

            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'status' => $to,
            ]);
        }
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $invalid = [
            ['nieuw', 'in_behandeling'],
            ['nieuw', 'opgelost'],
            ['nieuw', 'gesloten'],
            ['nieuw', 'toegewezen'],
            ['toegewezen', 'afgewezen'],
            ['toegewezen', 'opgelost'],
            ['in_behandeling', 'gesloten'],
            ['in_behandeling', 'nieuw'],
            ['wacht_op_melder', 'opgelost'],
            ['opgelost', 'afgewezen'],
            ['afgewezen', 'in_behandeling'],
            ['gesloten', 'nieuw'],
            ['gesloten', 'in_behandeling'],
        ];

        foreach ($invalid as [$from, $to]) {
            $ticket = $this->makeTicket(['status' => $from]);

            $this->actingAs($admin)
                ->patch(route('tickets.status', $ticket), ['status' => $to])
                ->assertForbidden();

            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'status' => $from,
            ]);
        }
    }

    public function test_an_unknown_status_is_rejected_by_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->patch(route('tickets.status', $ticket), ['status' => 'bestaat_niet'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'nieuw',
        ]);
    }

    public function test_a_melder_can_not_change_the_status_of_a_ticket(): void
    {
        $melder = User::factory()->create();
        $ticket = $this->makeTicket([
            'user_id' => $melder->id,
            'status' => 'in_behandeling',
        ]);

        $this->actingAs($melder)
            ->patch(route('tickets.status', $ticket), ['status' => 'opgelost'])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_behandeling',
        ]);
    }

    public function test_a_developer_can_not_change_the_status_of_an_unassigned_ticket(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($developer)
            ->patch(route('tickets.status', $ticket), ['status' => 'afgewezen'])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'nieuw',
        ]);
    }

    public function test_a_developer_can_not_change_the_status_of_a_ticket_assigned_to_someone_else(): void
    {
        $owner = User::factory()->developer()->create();
        $other = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $owner->id,
            'status' => 'toegewezen',
        ]);

        $this->actingAs($other)
            ->patch(route('tickets.status', $ticket), ['status' => 'in_behandeling'])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'toegewezen',
        ]);
    }

    public function test_the_assigned_developer_can_change_the_status(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'toegewezen',
        ]);

        $response = $this->actingAs($developer)
            ->patch(route('tickets.status', $ticket), ['status' => 'in_behandeling']);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas(
            'success',
            "Status van ticket {$ticket->ticket_number} is gewijzigd naar In behandeling."
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_behandeling',
        ]);
    }

    public function test_a_beheerder_can_change_the_status_of_any_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'in_behandeling',
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.status', $ticket), ['status' => 'opgelost'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'opgelost',
        ]);
    }

    public function test_a_status_change_is_recorded_in_the_history(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->patch(route('tickets.status', $ticket), ['status' => 'afgewezen'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'type' => 'updated',
            'field' => 'status',
            'old_value' => 'nieuw',
            'new_value' => 'afgewezen',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Status gewijzigd')
            ->assertSee('Afgewezen');
    }

    public function test_the_detail_page_only_shows_the_allowed_status_buttons(): void
    {
        $developer = User::factory()->developer()->create();

        $inProgress = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'in_behandeling',
        ]);

        $this->actingAs($developer)
            ->get(route('tickets.show', $inProgress))
            ->assertOk()
            ->assertSee('name="status" value="wacht_op_melder"', false)
            ->assertSee('name="status" value="opgelost"', false)
            ->assertSee('Wacht op melder')
            ->assertSee('Opgelost')
            ->assertDontSee('name="status" value="afgewezen"', false)
            ->assertDontSee('name="status" value="gesloten"', false)
            ->assertDontSee('name="status" value="toegewezen"', false)
            ->assertDontSee('name="status" value="in_behandeling"', false);

        $resolved = $this->makeTicket([
            'assignee_id' => $developer->id,
            'status' => 'opgelost',
        ]);

        $this->actingAs($developer)
            ->get(route('tickets.show', $resolved))
            ->assertOk()
            ->assertSee('Heropenen')
            ->assertSee('Sluiten')
            ->assertSee('name="status" value="in_behandeling"', false)
            ->assertSee('name="status" value="gesloten"', false);
    }

    public function test_the_detail_page_shows_no_status_buttons_for_a_closed_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket(['status' => 'gesloten']);

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('name="status" value=', false);
    }

    public function test_status_buttons_are_hidden_from_users_without_permission(): void
    {
        $melder = User::factory()->create();
        $otherDeveloper = User::factory()->developer()->create();
        $owner = User::factory()->developer()->create();
        $ticket = $this->makeTicket([
            'assignee_id' => $owner->id,
            'status' => 'in_behandeling',
        ]);

        foreach ([$melder, $otherDeveloper] as $user) {
            $this->actingAs($user)
                ->get(route('tickets.show', $ticket))
                ->assertOk()
                ->assertDontSee(route('tickets.status', $ticket))
                ->assertDontSee('name="status" value=', false);
        }
    }
}
