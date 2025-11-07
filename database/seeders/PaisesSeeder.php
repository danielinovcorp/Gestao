<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaisesSeeder extends Seeder
{
	public function run(): void
	{
		$paises = [
			['id' => 1, 'nome' => 'Portugal', 'iso' => 'PT'],
			['id' => 2, 'nome' => 'Espanha', 'iso' => 'ES'],
			['id' => 3, 'nome' => 'França', 'iso' => 'FR'],
			['id' => 4, 'nome' => 'Alemanha', 'iso' => 'DE'],
			['id' => 5, 'nome' => 'Itália', 'iso' => 'IT'],
			['id' => 6, 'nome' => 'Reino Unido', 'iso' => 'GB'],
			['id' => 7, 'nome' => 'Brasil', 'iso' => 'BR'],
			['id' => 8, 'nome' => 'Estados Unidos', 'iso' => 'US'],
			['id' => 9, 'nome' => 'Angola', 'iso' => 'AO'],
			['id' => 10, 'nome' => 'Moçambique', 'iso' => 'MZ'],
		];

		foreach ($paises as $pais) {
			DB::table('paises')->updateOrInsert(
				['id' => $pais['id']],
				$pais
			);
		}
	}
}
