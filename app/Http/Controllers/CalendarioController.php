<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalendarioController extends Controller
{
	public function index()
	{
		return [
			['id' => 1, 'title' => 'Exemplo', 'start' => now()->addDay()->toIso8601String(), 'end' => now()->addDay()->addHour()->toIso8601String()]
		];
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'title' => 'required|string|max:255',
			'start' => 'required|date',
			'end'   => 'nullable|date|after_or_equal:start',
		]);

		// TODO: salvar na tabela events
		return response()->json($data, 201);
	}
}
