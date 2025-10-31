<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class ContactosController extends Controller
{
	public function index(Request $request)
	{
		// Renderiza a página Vue. Dentro dela, teu composable chama /api/contactos
		return Inertia::render('Contactos/Index', [
			'search' => (string) $request->query('q', ''), // opcional
		]);
	}
}
