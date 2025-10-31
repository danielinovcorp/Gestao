<?php

namespace App\Http\Controllers;

use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CalendarEventController extends Controller
{
	public function index(Request $request)
	{
		$request->validate([
			'start' => ['required', 'date'],
			'end'   => ['required', 'date', 'after:start'],
			'user_id' => ['nullable', 'integer', 'exists:users,id'],
			'entidade_id' => ['nullable', 'integer', 'exists:entidades,id'],
		]);

		$query = CalendarEvent::with(['tipo', 'acao', 'entidade', 'responsavel'])
			->whereBetween('start', [$request->date('start'), $request->date('end')]);

		if ($request->filled('user_id')) $query->where('user_id', $request->integer('user_id'));
		if ($request->filled('entidade_id')) $query->where('entidade_id', $request->integer('entidade_id'));

		$user = $request->user();
		if (!$user->hasRole('admin')) {
			$query->where(function ($q) use ($user) {
				$q->where('created_by', $user->id)
					->orWhere('user_id', $user->id)
					->orWhere('partilha_global', true)
					->orWhereIn('id', function ($sub) use ($user) {
						$sub->select('calendar_event_id')->from('calendar_event_shares')->where('user_id', $user->id);
					})
					->orWhereIn('id', function ($sub) use ($user) {
						$sub->select('calendar_event_id')->from('calendar_event_ccs')->where('user_id', $user->id);
					});
			});
		}

		return CalendarEventResource::collection($query->orderBy('start')->get());
	}

	public function store(Request $request)
	{
		$data = $this->validateData($request);
		$data['created_by'] = $request->user()->id;
		$data['end'] = (clone $data['start'])->addMinutes($data['duration_minutes']);

		/** @var CalendarEvent $event */
		$event = CalendarEvent::create($data);
		$event->partilhas()->sync($request->input('partilhas', []));
		$event->conhecimentos()->sync($request->input('conhecimentos', []));

		return new CalendarEventResource($event->load(['tipo', 'acao', 'entidade', 'responsavel']));
	}

	public function update(Request $request, CalendarEvent $event)
	{
		Gate::authorize('modify', $event);

		$data = $this->validateData($request, updating: true);

		if (isset($data['start']) || isset($data['duration_minutes'])) {
			$start = $data['start'] ?? $event->start;
			$dur   = $data['duration_minutes'] ?? $event->duration_minutes;
			$data['end'] = (clone $start)->addMinutes($dur);
		}

		$event->update($data);

		if ($request->has('partilhas')) $event->partilhas()->sync($request->input('partilhas', []));
		if ($request->has('conhecimentos')) $event->conhecimentos()->sync($request->input('conhecimentos', []));

		return new CalendarEventResource($event->fresh(['tipo', 'acao', 'entidade', 'responsavel']));
	}

	public function destroy(Request $request, CalendarEvent $event)
	{
		Gate::authorize('modify', $event);
		$event->delete();
		return response()->noContent();
	}

	private function validateData(Request $request, bool $updating = false): array
	{
		$rules = [
			'user_id' => ['nullable', 'exists:users,id'],
			'entidade_id' => ['nullable', 'exists:entidades,id'],
			'calendar_type_id' => ['nullable', 'exists:calendar_types,id'],
			'calendar_action_id' => ['nullable', 'exists:calendar_actions,id'],
			'start' => [$updating ? 'sometimes' : 'required', 'date'],
			'duration_minutes' => [$updating ? 'sometimes' : 'required', 'integer', 'min:5', 'max:1440'],
			'descricao' => ['nullable', 'string', 'max:1000'],
			'estado' => ['sometimes', Rule::in(['agendado', 'concluido', 'cancelado'])],
			'partilha_global' => ['sometimes', 'boolean'],
			'conhecimento_global' => ['sometimes', 'boolean'],
			'partilhas' => ['sometimes', 'array'],
			'partilhas.*' => ['integer', 'exists:users,id'],
			'conhecimentos' => ['sometimes', 'array'],
			'conhecimentos.*' => ['integer', 'exists:users,id'],
		];

		$data = $request->validate($rules);

		if (isset($data['start']) && !$data['start'] instanceof Carbon) {
			$data['start'] = Carbon::parse($data['start']);
		}

		return $data;
	}
}
