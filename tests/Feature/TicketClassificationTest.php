<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketClassificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'naam' => 'Classificatie test',
            'beschrijving' => 'De categorie en prioriteit van dit ticket worden getest.',
            'status' => 'nieuw',
        ], $attributes));
    }

    public function test_the_standard_categories_and_priorities_are_available(): void
    {
        $this->assertCount(5, Category::options());
        $this->assertSame('Bug', Category::labelFor('bug'));
        $this->assertSame('Storing', Category::labelFor('storing'));
        $this->assertSame('Wijzigingsverzoek', Category::labelFor('wijzigingsverzoek'));
        $this->assertSame('Vraag', Category::labelFor('vraag'));
        $this->assertSame('Toegang/account', Category::labelFor('toegang_account'));

        $this->assertSame([
            'kritiek' => 'Kritiek',
            'hoog' => 'Hoog',
            'normaal' => 'Normaal',
            'laag' => 'Laag',
        ], TicketPriority::options());
    }

    public function test_a_new_ticket_defaults_to_vraag_and_normaal(): void
    {
        $ticket = $this->makeTicket();

        $this->assertSame('vraag', $ticket->categorie);
        $this->assertSame('normaal', $ticket->prioriteit);
        $this->assertSame('Vraag', $ticket->categoryLabel($ticket->categorie));
        $this->assertSame('Normaal', $ticket->priorityLabel($ticket->prioriteit));
    }

    public function test_a_category_is_required_when_creating_a_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'naam' => 'Zonder categorie',
                'beschrijving' => 'Beschrijving',
            ])
            ->assertSessionHasErrors('categorie');

        $this->assertDatabaseMissing('tickets', [
            'naam' => 'Zonder categorie',
        ]);
    }

    public function test_an_unknown_category_is_rejected_when_creating_a_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'naam' => 'Onbekende categorie',
                'beschrijving' => 'Beschrijving',
                'categorie' => 'onzin',
            ])
            ->assertSessionHasErrors('categorie');
    }

    public function test_the_priority_is_always_normaal_when_creating_a_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tickets.store'), [
            'naam' => 'Prioriteit test',
            'beschrijving' => 'Beschrijving',
            'categorie' => 'bug',
            'prioriteit' => 'kritiek',
        ]);

        $this->assertDatabaseHas('tickets', [
            'naam' => 'Prioriteit test',
            'categorie' => 'bug',
            'prioriteit' => 'normaal',
        ]);
    }

    public function test_a_developer_can_change_the_category_and_priority(): void
    {
        $developer = User::factory()->developer()->create();
        $ticket = $this->makeTicket();

        $response = $this->actingAs($developer)
            ->patch(route('tickets.classification', $ticket), [
                'categorie' => 'storing',
                'prioriteit' => 'hoog',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'categorie' => 'storing',
            'prioriteit' => 'hoog',
        ]);
    }

    public function test_a_beheerder_can_change_the_category_and_priority(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->patch(route('tickets.classification', $ticket), [
                'categorie' => 'toegang_account',
                'prioriteit' => 'kritiek',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'categorie' => 'toegang_account',
            'prioriteit' => 'kritiek',
        ]);
    }

    public function test_a_melder_can_not_change_the_category_and_priority(): void
    {
        $melder = User::factory()->melder()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($melder)
            ->patch(route('tickets.classification', $ticket), [
                'categorie' => 'bug',
                'prioriteit' => 'kritiek',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'categorie' => 'vraag',
            'prioriteit' => 'normaal',
        ]);
    }

    public function test_an_unknown_category_or_priority_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->patch(route('tickets.classification', $ticket), [
                'categorie' => 'onzin',
                'prioriteit' => 'onzin',
            ])
            ->assertSessionHasErrors(['categorie', 'prioriteit']);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'categorie' => 'vraag',
            'prioriteit' => 'normaal',
        ]);
    }

    public function test_changes_to_the_category_and_priority_are_added_to_the_history(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)->patch(route('tickets.classification', $ticket), [
            'categorie' => 'wijzigingsverzoek',
            'prioriteit' => 'laag',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'updated',
            'field' => 'categorie',
            'old_value' => 'vraag',
            'new_value' => 'wijzigingsverzoek',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => 'updated',
            'field' => 'prioriteit',
            'old_value' => 'normaal',
            'new_value' => 'laag',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Wijzigingsverzoek')
            ->assertSee('Laag');
    }

    public function test_the_classification_form_is_visible_for_developers_and_beheerders(): void
    {
        $ticket = $this->makeTicket();
        $action = 'action="'.route('tickets.classification', $ticket).'"';

        $this->actingAs(User::factory()->developer()->create())
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Classificatie opslaan')
            ->assertSee($action, false);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Classificatie opslaan')
            ->assertSee($action, false);
    }

    public function test_the_classification_form_is_not_visible_for_melders(): void
    {
        $melder = User::factory()->melder()->create();
        $ticket = $this->makeTicket(['user_id' => $melder->id]);

        $this->actingAs($melder)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Classificatie opslaan')
            ->assertDontSee('action="'.route('tickets.classification', $ticket).'"', false);
    }

    public function test_the_index_shows_the_category_and_priority(): void
    {
        $user = User::factory()->create();
        $this->makeTicket([
            'user_id' => $user->id,
            'naam' => 'Zichtbare classificatie',
            'categorie' => 'bug',
            'prioriteit' => 'hoog',
        ]);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Categorie')
            ->assertSee('Prioriteit')
            ->assertSee('Bug')
            ->assertSee('Hoog');
    }
}
