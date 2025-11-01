<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class HandleInertiaRequests extends Middleware
{
	protected $rootView = 'app';

	public function share(Request $request): array
	{
		return array_merge(parent::share($request), [
			'auth' => [
				'user' => $request->user(),
			],

			// ---- Partilha global da empresa (sem depender de um Model específico)
			'company' => function () {
				try {
					$row = null;

					// 1) Se existir um Model explícito, usamos
					if (class_exists(\App\Models\Empresa::class)) {
						$row = \App\Models\Empresa::query()->first();
					} elseif (class_exists(\App\Models\Company::class)) {
						$row = \App\Models\Company::query()->first();
					}

					// 2) Se não houver Model, tentamos por tabela (ajusta se precisa)
					if (!$row) {
						if (DB::getSchemaBuilder()->hasTable('empresas')) {
							$row = DB::table('empresas')->first();
						} elseif (DB::getSchemaBuilder()->hasTable('empresa')) {
							$row = DB::table('empresa')->first();
						} elseif (DB::getSchemaBuilder()->hasTable('settings_empresa')) {
							$row = DB::table('settings_empresa')->first();
						}
					}

					if (!$row) {
						return null;
					}

					// Monta logo_url a partir do que existir
					$logoUrl = null;

					// Se a coluna já for url pronta:
					if (!empty($row->logo_url)) {
						$logoUrl = $row->logo_url;
					}

					// Se existir caminho (logo_path), tentamos criar uma URL pública
					if (!$logoUrl && !empty($row->logo_path)) {
						// a) Se usas disco 'public'
						if (Storage::disk('public')->exists($row->logo_path)) {
							$logoUrl = Storage::disk('public')->url($row->logo_path);
						}
						// b) Se serves por rota protegida tipo /files?path=...
						elseif (Route::has('files.show')) {
							$logoUrl = route('files.show', ['path' => $row->logo_path]);
						}
					}

					return [
						'id'       => $row->id ?? null,
						'nome'     => $row->nome ?? null,
						'logo_url' => $logoUrl,
					];
				} catch (\Throwable $e) {
					// Nunca quebres a app por causa da logo
					return null;
				}
			},
		]);
	}
}
