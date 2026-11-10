<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('applications.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_can_not_manage_applications(): void
    {
        $developer = User::factory()->developer()->create();
        $melder = User::factory()->melder()->create();
        $application = Application::create(['naam' => 'Bestaande applicatie']);

        foreach ([$developer, $melder] as $user) {
            $this->actingAs($user)->get(route('applications.index'))->assertForbidden();
            $this->actingAs($user)->get(route('applications.create'))->assertForbidden();
            $this->actingAs($user)->post(route('applications.store'), [
                'naam' => 'Verboden applicatie',
            ])->assertForbidden();
            $this->actingAs($user)->get(route('applications.edit', $application))->assertForbidden();
            $this->actingAs($user)->put(route('applications.update', $application), [
                'naam' => 'Verboden wijziging',
            ])->assertForbidden();
            $this->actingAs($user)->patch(route('applications.toggle-active', $application))->assertForbidden();
        }

        $this->assertDatabaseMissing('applications', ['naam' => 'Verboden applicatie']);
        $this->assertSame('Bestaande applicatie', $application->fresh()->naam);
        $this->assertTrue($application->fresh()->is_active);
    }

    public function test_admins_can_view_the_application_index(): void
    {
        $admin = User::factory()->admin()->create();
        Application::create([
            'naam' => 'Intranet',
            'url' => 'https://intranet.example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee('Applicaties')
            ->assertSee('Intranet')
            ->assertSee('https://intranet.example.com');
    }

    public function test_admins_can_create_an_application(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('applications.store'), [
                'naam' => 'Klantportaal',
                'url' => 'https://portaal.example.com',
            ])
            ->assertRedirect(route('applications.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'naam' => 'Klantportaal',
            'url' => 'https://portaal.example.com',
            'is_active' => true,
        ]);
    }

    public function test_admins_can_update_an_application(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::create([
            'naam' => 'Oude naam',
            'url' => 'https://oud.example.com',
        ]);

        $this->actingAs($admin)
            ->put(route('applications.update', $application), [
                'naam' => 'Nieuwe naam',
                'url' => 'https://nieuw.example.com',
            ])
            ->assertRedirect(route('applications.index'))
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame('Nieuwe naam', $application->naam);
        $this->assertSame('https://nieuw.example.com', $application->url);
    }

    public function test_the_website_of_an_application_is_optional(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::create([
            'naam' => 'Zonder website',
            'url' => 'https://voorbeeld.example.com',
        ]);

        $this->actingAs($admin)->put(route('applications.update', $application), [
            'naam' => 'Zonder website',
            'url' => '',
        ])->assertRedirect(route('applications.index'));

        $this->assertNull($application->fresh()->url);
    }

    public function test_admins_can_deactivate_and_reactivate_an_application(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::create(['naam' => 'Te deactiveren']);

        $this->actingAs($admin)
            ->patch(route('applications.toggle-active', $application))
            ->assertSessionHas('success');

        $this->assertFalse($application->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('applications.toggle-active', $application))
            ->assertSessionHas('success');

        $this->assertTrue($application->fresh()->is_active);
    }

    public function test_application_input_is_validated(): void
    {
        $admin = User::factory()->admin()->create();
        Application::create(['naam' => 'Bestaande applicatie']);

        $this->actingAs($admin)
            ->post(route('applications.store'), [])
            ->assertSessionHasErrors('naam');

        $this->actingAs($admin)
            ->post(route('applications.store'), [
                'naam' => 'Bestaande applicatie',
            ])
            ->assertSessionHasErrors('naam');

        $this->actingAs($admin)
            ->post(route('applications.store'), [
                'naam' => 'Ongeldige website',
                'url' => 'geen-url',
            ])
            ->assertSessionHasErrors('url');
    }
}
