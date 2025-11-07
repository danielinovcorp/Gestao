<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EntidadeController as ApiEntidadeController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\ArtigoController;
use App\Http\Controllers\ViesController;
use App\Http\Controllers\CalendarEventController;
use Illuminate\Support\Facades\DB;

Route::middleware(['web', 'auth'])->group(function () {

	Route::apiResource('entidades', ApiEntidadeController::class)
		->names('api.entidades');

	Route::get('/paises', function () {
		return DB::table('paises')->select('id', 'nome')->orderBy('nome')->get();
	})->name('api.paises');

	Route::apiResource('contactos', ContactoController::class)->names('api.contactos');
	Route::apiResource('artigos',   ArtigoController::class)->names('api.artigos');

	Route::post('vies/validate', [ViesController::class, 'validate'])->name('api.vies.validate');

	Route::prefix('calendar')->name('api.calendar.')->group(function () {
		Route::get('events', [CalendarEventController::class, 'index'])->name('index');
		Route::post('events', [CalendarEventController::class, 'store'])->name('store');
		Route::put('events/{event}', [CalendarEventController::class, 'update'])->name('update');
		Route::delete('events/{event}', [CalendarEventController::class, 'destroy'])->name('destroy');
	});

	Route::get('/entidades/check-nif', function (\Illuminate\Http\Request $r) {
		$nifNorm = \App\Models\Entidade::normalizeNif((string) $r->query('nif', ''));
		if ($nifNorm === '') return ['exists' => false];
		$exists = \App\Models\Entidade::where('nif_hash', hash('sha256', $nifNorm))->exists();
		return ['exists' => $exists];
	})->name('api.entidades.check-nif');
});
