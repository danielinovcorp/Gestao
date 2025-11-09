<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HandleInertiaRequests extends Middleware
{
	protected $rootView = 'app';

	public function share(Request $request): array
	{
		return array_merge(parent::share($request), [
			// ✅ ADICIONE ESTA LINHA (única alteração necessária)
			'csrf_token' => csrf_token(),

			'auth' => [
				'user' => $request->user(),
			],

			'company' => function () use ($request) {
				try {
					$row = DB::table('empresa')->where('id', 1)->first();

					if (!$row) {
						Log::warning('Tabela empresa vazia ou não encontrada');
						return null;
					}

					$logoUrl = null;
					if (!empty($row->logo_path)) {
						if (Storage::disk('private')->exists($row->logo_path)) {
							if (Route::has('company.logo')) {
								$logoUrl = route('company.logo');
							}
						}
					}

					return [
						'id'            => $row->id ?? null,
						'nome'          => $row->nome ?? null,
						'morada'        => $row->morada ?? null,
						'codigo_postal' => $row->codigo_postal ?? null,
						'localidade'    => $row->localidade ?? null,
						'nif'           => $row->nif ?? null,
						'logo_path'     => $row->logo_path ?? null,
						'logo_url'      => $logoUrl,
					];
				} catch (\Throwable $e) {
					Log::error('Error in HandleInertiaRequests company share', [
						'error' => $e->getMessage()
					]);
					return null;
				}
			},

			'flash' => function () use ($request) {
				return [
					'success' => $request->session()->get('success'),
					'error' => $request->session()->get('error'),
				];
			},
		]);
	}
}
