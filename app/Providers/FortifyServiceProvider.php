<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		//
	}

	public function boot(): void
	{
		// Ações padrão do Fortify (Breeze usa estas)
		Fortify::createUsersUsing(CreateNewUser::class);
		Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
		Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
		Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

		// Redireciona para o desafio 2FA quando aplicável
		Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

		// 👇 Define a página do desafio 2FA (Inertia + Vue)
		Fortify::twoFactorChallengeView(fn() => Inertia::render('Auth/TwoFactorChallenge'));

		// (Opcional) se quiseres também personalizar estas views:
		// Fortify::loginView(fn () => Inertia::render('Auth/Login'));
		// Fortify::registerView(fn () => Inertia::render('Auth/Register'));
		// Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));

		// Rate limiting
		RateLimiter::for('login', function (Request $request) {
			$throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
			return Limit::perMinute(5)->by($throttleKey);
		});

		RateLimiter::for('two-factor', function (Request $request) {
			return Limit::perMinute(5)->by($request->session()->get('login.id'));
		});
	}
}
