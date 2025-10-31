<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
	public function view(User $user, CalendarEvent $event): bool
	{
		if ($user->hasRole('admin')) return true;

		return (
			$event->partilha_global ||
			$event->created_by === $user->id ||
			$event->user_id === $user->id ||
			$event->partilhas()->where('user_id', $user->id)->exists() ||
			$event->conhecimentos()->where('user_id', $user->id)->exists()
		);
	}

	public function modify(User $user, CalendarEvent $event): bool
	{
		return $user->hasRole('admin') || $event->created_by === $user->id;
	}
}
