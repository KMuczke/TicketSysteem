<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_can_not_manage_categories(): void
    {
        $developer = User::factory()->developer()->create();
        $melder = User::factory()->melder()->create();
        $category = Category::firstOrFail();

        foreach ([$developer, $melder] as $user) {
            $this->actingAs($user)->get(route('categories.index'))->assertForbidden();
            $this->actingAs($user)->get(route('categories.create'))->assertForbidden();
            $this->actingAs($user)->post(route('categories.store'), [
                'naam' => 'Verboden categorie',
                'kleur' => 'red',
            ])->assertForbidden();
            $this->actingAs($user)->get(route('categories.edit', $category))->assertForbidden();
            $this->actingAs($user)->put(route('categories.update', $category), [
                'naam' => 'Verboden wijziging',
                'kleur' => 'red',
            ])->assertForbidden();
            $this->actingAs($user)->patch(route('categories.toggle-active', $category))->assertForbidden();
        }

        $this->assertDatabaseMissing('categories', ['naam' => 'Verboden categorie']);
        $this->assertSame('Bug', $category->fresh()->naam);
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_admins_can_view_the_category_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Categorieën')
            ->assertSee('Bug')
            ->assertSee('Storing')
            ->assertSee('Wijzigingsverzoek')
            ->assertSee('Vraag')
            ->assertSee('Toegang/account');
    }

    public function test_admins_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'naam' => 'Netwerk',
                'kleur' => 'blue',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'naam' => 'Netwerk',
            'slug' => 'netwerk',
            'kleur' => 'blue',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('categories.index'))
            ->assertSee('Netwerk');
    }

    public function test_admins_can_update_a_category_without_changing_its_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::where('slug', 'bug')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('categories.update', $category), [
                'naam' => 'Fout',
                'kleur' => 'indigo',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $category->refresh();

        $this->assertSame('Fout', $category->naam);
        $this->assertSame('bug', $category->slug);
        $this->assertSame('indigo', $category->kleur);
    }

    public function test_admins_can_deactivate_and_reactivate_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::where('slug', 'storing')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('categories.toggle-active', $category))
            ->assertSessionHas('success');

        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('categories.toggle-active', $category))
            ->assertSessionHas('success');

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_the_last_active_category_can_not_be_deactivated(): void
    {
        $admin = User::factory()->admin()->create();

        Category::query()->update(['is_active' => false]);
        $category = Category::where('slug', 'vraag')->firstOrFail();
        $category->update(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('categories.toggle-active', $category))
            ->assertSessionHas('error');

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_category_input_is_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('categories.store'), [])
            ->assertSessionHasErrors(['naam', 'kleur']);

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'naam' => 'Bug',
                'kleur' => 'red',
            ])
            ->assertSessionHasErrors('naam');

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'naam' => 'Nieuwe categorie',
                'kleur' => 'onbekend',
            ])
            ->assertSessionHasErrors('kleur');
    }

    public function test_duplicate_slugs_are_made_unique(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('categories.store'), [
            'naam' => 'Test Categorie',
            'kleur' => 'green',
        ]);

        $this->actingAs($admin)->post(route('categories.store'), [
            'naam' => 'Test-Categorie',
            'kleur' => 'green',
        ]);

        $this->assertDatabaseHas('categories', ['naam' => 'Test Categorie', 'slug' => 'test_categorie']);
        $this->assertDatabaseHas('categories', ['naam' => 'Test-Categorie', 'slug' => 'test_categorie_2']);
    }

    public function test_a_deactivated_category_is_not_available_for_new_tickets(): void
    {
        $melder = User::factory()->melder()->create();
        $category = Category::where('slug', 'storing')->firstOrFail();
        $category->update(['is_active' => false]);

        $this->actingAs($melder)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertDontSee('Storing');

        $this->actingAs($melder)
            ->post(route('tickets.store'), [
                'naam' => 'Ticket met oude categorie',
                'beschrijving' => 'Beschrijving',
                'categorie' => 'storing',
            ])
            ->assertSessionHasErrors('categorie');
    }

    public function test_existing_tickets_keep_a_deactivated_category_and_follow_renames(): void
    {
        $admin = User::factory()->admin()->create();
        $melder = User::factory()->melder()->create();
        $ticket = Ticket::create([
            'user_id' => $melder->id,
            'naam' => 'Bestaand ticket',
            'beschrijving' => 'Beschrijving',
            'categorie' => 'bug',
        ]);

        $category = Category::where('slug', 'bug')->firstOrFail();
        $category->update(['is_active' => false]);

        $this->actingAs($melder)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Bug');

        $this->actingAs($admin)->put(route('categories.update', $category), [
            'naam' => 'Fout',
            'kleur' => 'red',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'categorie' => 'bug',
        ]);

        $this->actingAs($melder)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Fout');

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Fout');
    }

    public function test_only_admins_see_the_category_and_application_menu_items(): void
    {
        $admin = User::factory()->admin()->create();
        $melder = User::factory()->melder()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Categorieën')
            ->assertSee('Applicaties');

        $this->actingAs($melder)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Categorieën')
            ->assertDontSee('Applicaties');
    }
}
