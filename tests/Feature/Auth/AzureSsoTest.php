<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AzureSsoTest extends TestCase
{
    use RefreshDatabase;

    private function mockAzureUser(string $id, string $email): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);

        $provider = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('azure')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);
    }

    public function test_azure_redirect_route_redirects_to_microsoft(): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('redirect')->andReturn(redirect('https://login.microsoftonline.com/fake'));

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('azure')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);

        $this->get(route('azure.redirect'))->assertRedirect();
    }

    public function test_callback_logs_in_user_matched_by_azure_id(): void
    {
        $user = User::factory()->create(['azure_id' => 'azure-abc-123']);

        $this->mockAzureUser('azure-abc-123', $user->email);

        $this->get(route('azure.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_azure_id_and_logs_in_user_matched_by_email(): void
    {
        $user = User::factory()->create(['azure_id' => null, 'email' => 'jane@example.com']);

        $this->mockAzureUser('azure-new-456', 'jane@example.com');

        $this->get(route('azure.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'azure_id' => 'azure-new-456']);
    }

    public function test_callback_rejects_unknown_microsoft_identity(): void
    {
        $this->mockAzureUser('azure-unknown', 'nobody@example.com');

        $this->get(route('azure.callback'))->assertRedirect(route('login'));
    }

    public function test_callback_rejects_inactive_user(): void
    {
        $user = User::factory()->create(['azure_id' => 'azure-inactive', 'is_active' => false]);

        $this->mockAzureUser('azure-inactive', $user->email);

        $this->get(route('azure.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_page_shows_sign_in_with_microsoft_button(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in with Microsoft');
    }
}
