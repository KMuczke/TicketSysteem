<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_can_view_the_user_list_read_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Jan Jansen']);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Jan Jansen')
            ->assertDontSee('Nieuwe gebruiker')
            ->assertDontSee(route('users.create'));
    }

    public function test_non_admin_users_can_not_create_or_modify_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->get(route('users.create'))->assertForbidden();
        $this->actingAs($user)->post(route('users.store'), [
            'name' => 'Verboden',
            'email' => 'verboden@example.com',
            'password' => 'password',
            'role' => UserRole::Beheerder->value,
        ])->assertForbidden();
        $this->actingAs($user)->get(route('users.edit', $other))->assertForbidden();
        $this->actingAs($user)->patch(route('users.toggle-active', $other))->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'verboden@example.com']);
        $this->assertTrue($other->fresh()->isActive());
    }

    public function test_the_users_menu_item_is_visible_for_all_users(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gebruikers');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gebruikers');
    }

    public function test_admins_can_view_the_user_index(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create(['name' => 'Jan Jansen']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Jan Jansen');
    }

    public function test_every_user_has_exactly_one_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Melder, $user->fresh()->role);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::Melder->value,
        ]);
    }

    public function test_the_available_roles_are_melder_developer_and_beheerder(): void
    {
        $this->assertSame([
            'melder' => 'Melder',
            'developer' => 'Developer',
            'beheerder' => 'Beheerder',
        ], UserRole::options());

        $this->assertSame('melder', UserRole::Melder->value);
        $this->assertSame('developer', UserRole::Developer->value);
        $this->assertSame('beheerder', UserRole::Beheerder->value);

        $this->assertNotSame('', UserRole::Melder->description());
        $this->assertNotSame('', UserRole::Developer->description());
        $this->assertNotSame('', UserRole::Beheerder->description());
    }

    public function test_beheerders_can_assign_the_developer_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nieuwe Developer',
            'email' => 'developer@example.com',
            'password' => 'password',
            'role' => UserRole::Developer->value,
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'developer@example.com',
            'role' => UserRole::Developer->value,
        ]);
    }

    public function test_developers_can_not_manage_users(): void
    {
        $developer = User::factory()->developer()->create();
        $other = User::factory()->create();

        $this->actingAs($developer)->get(route('users.create'))->assertForbidden();
        $this->actingAs($developer)->get(route('users.edit', $other))->assertForbidden();
        $this->actingAs($developer)->put(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'role' => UserRole::Developer->value,
            'is_active' => '1',
        ])->assertForbidden();

        $this->assertSame(UserRole::Melder, $other->fresh()->role);
    }

    public function test_admins_can_view_the_create_user_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.create'))->assertOk();
    }

    public function test_admins_can_view_the_edit_user_form(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->get(route('users.edit', $user))->assertOk();
    }

    public function test_admins_can_create_a_user_with_a_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nieuwe Beheerder',
            'email' => 'nieuw@example.com',
            'password' => 'password',
            'role' => UserRole::Beheerder->value,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nieuw@example.com',
            'role' => UserRole::Beheerder->value,
            'is_active' => true,
        ]);
    }

    public function test_admins_can_change_a_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Beheerder->value,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame(UserRole::Beheerder, $user->fresh()->role);
    }

    public function test_admins_can_deactivate_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('users.toggle-active', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->isActive());
    }

    public function test_deactivated_users_can_not_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_non_admins_can_not_assign_roles(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->put(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'role' => UserRole::Beheerder->value,
            'is_active' => '1',
        ])->assertForbidden();

        $this->assertSame(UserRole::Melder, $other->fresh()->role);
    }

    public function test_admins_can_not_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('users.toggle-active', $admin))
            ->assertRedirect();

        $this->assertTrue($admin->fresh()->isActive());
    }

    public function test_admins_can_not_remove_their_own_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::Melder->value,
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Beheerder, $admin->fresh()->role);
    }

    public function test_deactivating_a_user_keeps_existing_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        Ticket::create([
            'naam' => 'Ticket voor gedeactiveerde gebruiker',
            'beschrijving' => 'Moet bewaard blijven',
            'status' => 'nieuw',
        ]);

        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('users.toggle-active', $user));

        $this->assertFalse($user->fresh()->isActive());
        $this->assertDatabaseHas('tickets', ['naam' => 'Ticket voor gedeactiveerde gebruiker']);
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->get(route('tickets.index'))->assertOk();

        $user->update(['is_active' => false]);

        $this->get(route('tickets.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrorsIn('email');
    }
}
