<?php

namespace Database\Seeders;

use App\Models\Entidade;
use Illuminate\Database\Seeder;

class EntidadesSeeder extends Seeder
{
	public function run(): void
	{
		$next = (int) (Entidade::max('numero') ?? 0) + 1;

		$qClientes     = 20;
		$qFornecedores = 12;
		$qAmbos        = 6;

		Entidade::factory()->count($qClientes)->cliente()->make()
			->each(function ($e) use (&$next) {
				$e->numero = $next++;
				$e->save();
			});

		Entidade::factory()->count($qFornecedores)->fornecedor()->make()
			->each(function ($e) use (&$next) {
				$e->numero = $next++;
				$e->save();
			});

		Entidade::factory()->count($qAmbos)->ambos()->make()
			->each(function ($e) use (&$next) {
				$e->numero = $next++;
				$e->save();
			});
	}
}
