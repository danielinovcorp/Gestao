<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IvaSeeder extends Seeder
{
	public function run(): void
	{
		$now = now();

		$data = [
			['nome' => 'Isento',    'taxa' => 0.00,  'created_at' => $now, 'updated_at' => $now],
			['nome' => 'Reduzido',  'taxa' => 6.00,  'created_at' => $now, 'updated_at' => $now],
			['nome' => 'Intermédio', 'taxa' => 13.00, 'created_at' => $now, 'updated_at' => $now],
			['nome' => 'Normal',    'taxa' => 23.00, 'created_at' => $now, 'updated_at' => $now],
		];

		// evita duplicar se já houver
		foreach ($data as $row) {
			DB::table('iva')->updateOrInsert(
				['nome' => $row['nome']],
				$row
			);
		}
	}
}
