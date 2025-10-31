<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
	public function index(Request $r)
	{
		$q = Event::query()->where('user_id', $r->user()->id);

		// filtros opcionais: ?from=YYYY-MM-DD&to=YYYY-MM-DD
		if ($from = $r->query('from')) $q->where('start', '>=', $from);
		if ($to   = $r->query('to'))   $q->where('start', '<=', $to);

		return $q->orderBy('start')->get()->map(fn($e) => [
			'id'    => $e->id,
			'title' => $e->title,
			'start' => $e->start->toIso8601String(),
			'end'   => optional($e->end)->toIso8601String(),
			'color' => $e->color,
		]);
	}

	public function store(Request $r)
	{
		$data = $r->validate([
			'title' => 'required|string|max:255',
			'start' => 'required|date',
			'end'   => 'nullable|date|after_or_equal:start',
			'color' => 'nullable|string|max:20',
		]);

		$event = Event::create($data + ['user_id' => $r->user()->id]);

		return response()->json([
			'id' => $event->id,
			'title' => $event->title,
			'start' => $event->start->toIso8601String(),
			'end' => optional($event->end)->toIso8601String(),
			'color' => $event->color,
		], 201);
	}

	public function update(Request $r, Event $event)
	{
		// garante que o evento é do user autenticado
		abort_if($event->user_id !== $r->user()->id, 403);

		$data = $r->validate([
			'title' => 'sometimes|string|max:255',
			'start' => 'sometimes|date',
			'end'   => 'sometimes|nullable|date|after_or_equal:start',
			'color' => 'sometimes|nullable|string|max:20',
		]);

		$event->update($data);
		return response()->json(['ok' => true]);
	}

	public function destroy(Request $r, Event $event)
	{
		abort_if($event->user_id !== $r->user()->id, 403);
		$event->delete();
		return response()->noContent();
	}
}
