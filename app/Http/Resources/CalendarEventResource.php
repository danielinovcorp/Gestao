<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'title' => $this->titulo(),
			'start' => $this->start->toIso8601String(),
			'end' => $this->end->toIso8601String(),
			'extendedProps' => [
				'estado' => $this->estado,
				'descricao' => $this->descricao,
				'tipo' => optional($this->tipo)->nome,
				'acao' => optional($this->acao)->nome,
				'entidade' => optional($this->entidade)->nome ?? null,
				'responsavel' => optional($this->responsavel)->name ?? null,
			],
		];
	}
}
