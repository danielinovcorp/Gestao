<?php

use Illuminate\Support\Facades\Route;

// ✅ Controladores API principais
use App\Http\Controllers\Api\EntidadeController as ApiEntidadeController;
use App\Http\Controllers\Api\CalendarioController as ApiCalendarioController; // (pode remover se não usar mais)
use App\Http\Controllers\CalendarEventController; // 👈 novo controlador real de eventos

// ✅ Controladores web temporários (mock API até migração completa)
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\ArtigoController;
use App\Http\Controllers\ViesController;

// ============================================================================
// ROTAS API
// Todas protegidas por Sanctum (usuário autenticado via token ou sessão SPA)
// ============================================================================
Route::middleware(['auth:sanctum'])->group(function () {

	// ------------------------------------------------------------------------
	// ENTIDADES (controlador API completo)
	// ------------------------------------------------------------------------
	Route::apiResource('entidades', ApiEntidadeController::class)
		->names('api.entidades'); // 👈 prefixo evita conflito com web

	// ------------------------------------------------------------------------
	// CONTACTOS / ARTIGOS (controladores web, respostas JSON por enquanto)
	// ------------------------------------------------------------------------
	Route::apiResource('contactos', ContactoController::class)
		->names('api.contactos');

	Route::apiResource('artigos', ArtigoController::class)
		->names('api.artigos');

	// ------------------------------------------------------------------------
	// VIES - Validação de NIF
	// ------------------------------------------------------------------------
	Route::post('vies/validate', [ViesController::class, 'validate'])
		->name('api.vies.validate');

	// ------------------------------------------------------------------------
	// CALENDÁRIO - Eventos (CRUD completo)
	// ------------------------------------------------------------------------
	Route::prefix('calendar')->name('api.calendar.')->group(function () {

		// Feed de eventos (FullCalendar)
		Route::get('events', [CalendarEventController::class, 'index'])
			->name('index');

		// Criar novo evento
		Route::post('events', [CalendarEventController::class, 'store'])
			->name('store');

		// Atualizar evento existente
		Route::put('events/{event}', [CalendarEventController::class, 'update'])
			->name('update');

		// Remover evento
		Route::delete('events/{event}', [CalendarEventController::class, 'destroy'])
			->name('destroy');
	});

	// ------------------------------------------------------------------------
	// CHECAGEM DE NIF (VIES local)
	// ------------------------------------------------------------------------
	Route::get('/entidades/check-nif', function (\Illuminate\Http\Request $r) {
		$nifNorm = \App\Models\Entidade::normalizeNif((string) $r->query('nif', ''));
		if ($nifNorm === '') return ['exists' => false];
		$exists = \App\Models\Entidade::where('nif_hash', hash('sha256', $nifNorm))->exists();
		return ['exists' => $exists];
	})->name('api.entidades.check-nif');
});
