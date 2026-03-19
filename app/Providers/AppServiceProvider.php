<?php

namespace App\Providers;

use App\Models\Component;
use App\Models\ComponentType;
use App\Models\FactDefinition;
use App\Models\Tag;
use App\Models\User;
use App\Policies\ComponentPolicy;
use App\Policies\ComponentTypePolicy;
use App\Policies\FactDefinitionPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\AzureExtendSocialite;
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
        Gate::policy(ComponentType::class, ComponentTypePolicy::class);
        Gate::policy(FactDefinition::class, FactDefinitionPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class.'@handle');
    }
}
