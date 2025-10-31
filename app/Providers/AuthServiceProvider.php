<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
	protected $policies = [
		\App\Models\Doc::class => \App\Policies\DocPolicy::class,
		\App\Models\CalendarEvent::class => \App\Policies\CalendarEventPolicy::class,

	];

	public function boot(): void
	{
		$this->registerPolicies();

		// Permissão global opcional: Admin tem acesso total
		Gate::before(function ($user, $ability) {
			if ($user->hasRole('admin')) {
				return true;
			}
		});
	}
}
