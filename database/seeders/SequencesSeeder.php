<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SequencesSeeder extends Seeder
{
	public function run(): void
	{
		// Certifica que existe a tabela 'sequences'
		if (!DB::getSchemaBuilder()->hasTable('sequences')) {
			return;
		}

		// Descobre o último número existente em contactos
		$maxContactos = DB::table('contactos')->max('numero') ?? 0;

		// Atualiza ou cria a sequência de contactos
		DB::table('sequences')->updateOrInsert(
			['key' => 'contactos'],
			[
				'next' => $maxContactos + 1,
				'created_at' => now(),
				'updated_at' => now(),
			]
		);

		// (Opcional) Se quiser garantir outras chaves também:
		$maxEntidades = DB::table('entidades')->max('numero') ?? 0;
		DB::table('sequences')->updateOrInsert(
			['key' => 'entidades'],
			[
				'next' => $maxEntidades + 1,
				'created_at' => now(),
				'updated_at' => now(),
			]
		);

		// Adiciona outras sequências conforme teu sistema
		// Exemplo:
		// DB::table('sequences')->updateOrInsert(['key' => 'propostas'], ['next' => 1, ...]);
	}
}
