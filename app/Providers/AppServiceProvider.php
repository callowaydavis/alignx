<?php

namespace App\Providers;

use App\Models\Component;
use App\Models\FactDefinition;
use App\Models\User;
use App\Policies\ComponentPolicy;
use App\Policies\FactDefinitionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Component::class, ComponentPolicy::class);
        Gate::policy(FactDefinition::class, FactDefinitionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(SocialiteWasCalled::class, \SocialiteProviders\Azure\AzureExtendSocialite::class.'@handle');
    }
}
