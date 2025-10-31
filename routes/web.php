<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\Access\UsersController;
use App\Http\Controllers\Access\RolesController;
use App\Http\Controllers\PropostasController;
use App\Http\Controllers\AjaxLookupController;
use App\Http\Controllers\OrdemTrabalhoController;

Route::get('/', function () {
	return Inertia::render('Welcome', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'laravelVersion' => Application::VERSION,
		'phpVersion' => PHP_VERSION,
	]);
});

Route::get('/dashboard', function () {
	return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
	// Entidades
	Route::get('/clientes', [\App\Http\Controllers\EntidadesController::class, 'index'])->name('entidades.index.clientes');
	Route::get('/fornecedores', [\App\Http\Controllers\EntidadesController::class, 'index'])->name('entidades.index.fornecedores');

	Route::get('/contactos',  [\App\Http\Controllers\ContactosController::class, 'index'])->name('contactos.index');
	Route::get('/calendario', fn() => inertia('Calendario/Index'))->name('calendario.index');


	Route::get('/ordens-trabalho', [\App\Http\Controllers\OrdensTrabalhoController::class, 'index'])->name('ot.index');

	// ---------------------------
	// Arquivos Digitais (Docs)
	// ---------------------------
	Route::prefix('docs')->name('docs.')->group(function () {
		Route::get('/',                 [DocController::class, 'index'])->name('index');
		Route::post('/',                [DocController::class, 'store'])->name('store');
		Route::patch('/{doc}',          [DocController::class, 'update'])->name('update');
		Route::delete('/{doc}',         [DocController::class, 'destroy'])->name('destroy');
		Route::get('/{doc}/download',   [DocController::class, 'download'])->name('download');
		Route::get('/{doc}/preview',    [DocController::class, 'preview'])->name('preview');
	});
	Route::get('/arquivo', fn() => redirect()->route('docs.index'))->name('arquivo.index');

	// ---------------------------
	// Propostas
	// ---------------------------
	Route::resource('propostas', PropostasController::class)->except(['create', 'edit', 'show']);
	Route::post('/propostas/{proposta}/fechar',              [PropostasController::class, 'fechar'])->name('propostas.fechar');
	Route::get('/propostas/{proposta}/pdf',                  [PropostasController::class, 'pdf'])->name('propostas.pdf');
	Route::post('/propostas/{proposta}/converter-encomenda', [PropostasController::class, 'converterEncomenda'])->name('propostas.converter');

	// Lookups/AJAX
	Route::get('/ajax/clientes',     [AjaxLookupController::class, 'clientes'])->name('ajax.clientes');
	Route::get('/ajax/fornecedores', [AjaxLookupController::class, 'fornecedores'])->name('ajax.fornecedores');
	Route::get('/ajax/artigos',      [AjaxLookupController::class, 'artigos'])->name('ajax.artigos');

	// Financeiro
	Route::prefix('financeiro')->name('financeiro.')->group(function () {
		Route::get('/contas',               [\App\Http\Controllers\Financeiro\ContasController::class, 'index'])->name('contas.index');
		Route::get('/cc-clientes',          [\App\Http\Controllers\Financeiro\ContaCorrenteClientesController::class, 'index'])->name('cc.clientes');
		Route::get('/faturas-fornecedores', [\App\Http\Controllers\Financeiro\FaturasFornecedoresController::class, 'index'])->name('faturas.fornecedores');
	});

	// ✅ Gestão de Acessos (com as tuas controllers em Access\*)
	Route::prefix('gestao-acessos')->name('access.')->group(function () {
		// Utilizadores
		Route::get('/utilizadores',                [UsersController::class, 'index'])
			->middleware('can:access.users.view')->name('users.index');

		Route::post('/utilizadores',               [UsersController::class, 'store'])
			->middleware('can:access.users.create')->name('users.store');

		Route::put('/utilizadores/{user}',         [UsersController::class, 'update'])
			->middleware('can:access.users.update')->name('users.update');

		Route::delete('/utilizadores/{user}',      [UsersController::class, 'destroy'])
			->middleware('can:access.users.delete')->name('users.destroy');

		Route::patch('/utilizadores/{user}/toggle', [UsersController::class, 'toggleStatus'])
			->middleware('can:access.users.update')->name('users.toggle');

		// Grupos (Roles)
		Route::get('/permissoes',                  [RolesController::class, 'index'])
			->middleware('can:access.roles.view')->name('roles.index');

		Route::post('/permissoes',                 [RolesController::class, 'store'])
			->middleware('can:access.roles.create')->name('roles.store');

		Route::put('/permissoes/{role}',           [RolesController::class, 'update'])
			->middleware('can:access.roles.update')->name('roles.update');

		Route::patch('/permissoes/{role}/toggle',  [RolesController::class, 'toggleStatus'])
			->middleware('can:access.roles.update')->name('roles.toggle');

		Route::delete('/permissoes/{role}',        [RolesController::class, 'destroy'])
			->middleware('can:access.roles.delete')->name('roles.destroy');
	});


	// ✅ Encomendas (consolidado, escolha a tua controladora final)
	Route::prefix('encomendas')->name('encomendas.')->group(function () {
		// CLIENTES
		Route::get('clientes',               [SalesOrderController::class, 'index'])->name('clientes.index');
		Route::get('clientes/create',        [SalesOrderController::class, 'create'])->name('clientes.create');
		Route::post('clientes',              [SalesOrderController::class, 'store'])->name('clientes.store');
		Route::patch('clientes/{order}/fechar',   [SalesOrderController::class, 'close'])->name('clientes.close');
		Route::post('clientes/{order}/converter', [SalesOrderController::class, 'convertToSupplierOrders'])->name('clientes.convert');
		Route::delete('clientes/{order}',    [SalesOrderController::class, 'destroy'])->name('clientes.destroy');

		// FORNECEDORES
		Route::get('fornecedores',           [PurchaseOrderController::class, 'index'])->name('fornecedores.index');
		Route::patch('fornecedores/{purchaseOrder}/marcar-paga', [PurchaseOrderController::class, 'markPaid'])->name('fornecedores.markPaid');
	});

	Route::resource('ordens', OrdemTrabalhoController::class)->except(['create', 'show', 'edit']);
});

require __DIR__ . '/auth.php';
