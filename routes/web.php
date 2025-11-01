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
use App\Http\Controllers\FornecedorFaturaController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\ContaBancariaController;
use App\Http\Controllers\ClienteMovimentoController;
use App\Http\Controllers\Config\PaisesController;
use App\Http\Controllers\Config\FuncoesContactoController;
use App\Http\Controllers\Config\CalendarioTiposController;
use App\Http\Controllers\Config\CalendarioAcoesController;
use App\Http\Controllers\Config\IvaController as ConfigIvaController;
use App\Http\Controllers\Config\EmpresaController as EmpresaConfigController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\Config\ArtigosController;


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

	// FINANCEIRO
	Route::prefix('financeiro')->name('financeiro.')->group(function () {

		// Faturas de Fornecedor
		Route::get('faturas-fornecedor', [FornecedorFaturaController::class, 'index'])->name('faturas-fornecedor.index');
		Route::get('faturas-fornecedor/create', [FornecedorFaturaController::class, 'create'])->name('faturas-fornecedor.create');
		Route::post('faturas-fornecedor', [FornecedorFaturaController::class, 'store'])->name('faturas-fornecedor.store');
		Route::get('faturas-fornecedor/{fornecedor_fatura}/edit', [FornecedorFaturaController::class, 'edit'])->name('faturas-fornecedor.edit');
		Route::put('faturas-fornecedor/{fornecedor_fatura}', [FornecedorFaturaController::class, 'update'])->name('faturas-fornecedor.update');
		Route::delete('faturas-fornecedor/{fornecedor_fatura}', [FornecedorFaturaController::class, 'destroy'])->name('faturas-fornecedor.destroy');

		// Contas Bancárias
		Route::get('contas-bancarias', [ContaBancariaController::class, 'index'])->name('contas-bancarias');
		Route::post('contas-bancarias', [ContaBancariaController::class, 'store'])->name('contas-bancarias.store');
		Route::put('contas-bancarias/{conta_bancaria}', [ContaBancariaController::class, 'update'])->name('contas-bancarias.update');
		Route::delete('contas-bancarias/{conta_bancaria}', [ContaBancariaController::class, 'destroy'])->name('contas-bancarias.destroy');

		// Conta Corrente Clientes
		Route::get('conta-corrente-clientes', [ClienteMovimentoController::class, 'index'])->name('conta-corrente-clientes');
		Route::post('conta-corrente-clientes', [ClienteMovimentoController::class, 'store'])->name('conta-corrente-clientes.store');
	});

	// ✅ Gestão de Acessos
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


	// ✅ Encomendas
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

	// ===============================
	// CONFIGURAÇÕES
	// ===============================
	Route::prefix('configuracoes')->name('config.')->group(function () {
		// Landing opcional (cards dos módulos)
		Route::get('/', fn() => inertia('Configuracoes/Index'))->name('index');

		// Países
		Route::prefix('paises')->name('paises.')->group(function () {
			Route::get('/', [PaisesController::class, 'index'])->name('index');
			Route::post('/', [PaisesController::class, 'store'])->name('store');
			Route::put('/{pais}', [PaisesController::class, 'update'])->name('update');
			Route::delete('/{pais}', [PaisesController::class, 'destroy'])->name('destroy');
		});

		// Contactos - Funções
		Route::prefix('funcoes-contacto')->name('funcoes-contacto.')->group(function () {
			Route::get('/', [FuncoesContactoController::class, 'index'])->name('index');
			Route::post('/', [FuncoesContactoController::class, 'store'])->name('store');
			Route::put('/{funcao}', [FuncoesContactoController::class, 'update'])->name('update');
			Route::delete('/{funcao}', [FuncoesContactoController::class, 'destroy'])->name('destroy');
		});

		// Calendário (abre por padrão em Tipos)
		Route::redirect('/calendario', '/configuracoes/calendario/tipos')->name('calendario');

		Route::prefix('calendario')->name('calendario.')->group(function () {
			// Tipos
			Route::prefix('tipos')->name('tipos.')->group(function () {
				Route::get('/', [CalendarioTiposController::class, 'index'])->name('index');
				Route::post('/', [CalendarioTiposController::class, 'store'])->name('store');
				Route::put('/{tipo}', [CalendarioTiposController::class, 'update'])->name('update');
				Route::delete('/{tipo}', [CalendarioTiposController::class, 'destroy'])->name('destroy');
			});

			// Ações
			Route::prefix('acoes')->name('acoes.')->group(function () {
				Route::get('/', [CalendarioAcoesController::class, 'index'])->name('index');
				Route::post('/', [CalendarioAcoesController::class, 'store'])->name('store');
				Route::put('/{acao}', [CalendarioAcoesController::class, 'update'])->name('update');
				Route::delete('/{acao}', [CalendarioAcoesController::class, 'destroy'])->name('destroy');
			});
		});

		// Financeiro - IVA
		Route::prefix('iva')->name('iva.')->group(function () {
			Route::get('/', [ConfigIvaController::class, 'index'])->name('index');
			Route::post('/', [ConfigIvaController::class, 'store'])->name('store');
			Route::put('/{iva}', [ConfigIvaController::class, 'update'])->name('update');
			Route::delete('/{iva}', [ConfigIvaController::class, 'destroy'])->name('destroy');
		});

		// Empresa (singleton: mostra e atualiza)
		Route::get('/empresa', [EmpresaConfigController::class, 'show'])->name('empresa');
		Route::match(['put', 'patch'], '/empresa', [EmpresaConfigController::class, 'update'])->name('empresa.update');
	});

	// ===============================
	// CONFIGURAÇÕES - ARTIGOS
	// ===============================
	Route::prefix('configuracoes/artigos')->name('config.artigos.')->group(function () {
		Route::get('/', [ArtigosController::class, 'index'])->name('index');
		Route::post('/', [ArtigosController::class, 'store'])->name('store');
		Route::put('/{artigo}', [ArtigosController::class, 'update'])->name('update');
		Route::delete('/{artigo}', [ArtigosController::class, 'destroy'])->name('destroy');
	});


	// ===============================
	// LOGS (somente leitura + opcional export)
	// ===============================
	Route::prefix('logs')->name('logs.')->group(function () {
		Route::get('/', [LogsController::class, 'index'])->name('index');
		// Opcional: export CSV
		// Route::get('/export', [LogsController::class, 'export'])->name('export');
	});
});

Route::get('/files/private', function (Request $request) {
	$request->validate(['path' => ['required', 'string']]);
	abort_unless(auth()->check(), 403);

	$path = $request->query('path');
	abort_unless(Storage::disk('private')->exists($path), 404);

	return response()->file(Storage::disk('private')->path($path));
})->name('files.private.show');

require __DIR__ . '/auth.php';
