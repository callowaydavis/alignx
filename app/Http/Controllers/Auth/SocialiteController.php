<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToAzure(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function handleAzureCallback(): RedirectResponse
    {
        $azureUser = Socialite::driver('azure')->user();

        $user = User::query()->where('azure_id', $azureUser->getId())->first()
            ?? User::query()->where('email', $azureUser->getEmail())->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'No account exists for this Microsoft identity. Please contact an administrator.',
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        if (! $user->azure_id) {
            $user->update(['azure_id' => $azureUser->getId()]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
