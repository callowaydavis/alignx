<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_as_guest(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login'))->assertRedirect();
    }

    public function test_valid_credentials_redirect_to_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_invalid_password_returns_email_error(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_unknown_email_returns_email_error(): void
    {
        $this->post(route('login.post'), [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unauthenticated_request_to_components_redirects_to_login(): void
    {
        $this->get(route('components.index'))->assertRedirect(route('login'));
    }
}
