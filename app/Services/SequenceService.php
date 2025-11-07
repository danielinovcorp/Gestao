<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SequenceService
{
	/**
	 * Gera o próximo número sequencial.
	 *
	 * @param  string  $key   Nome da sequência (ex.: 'sales_orders', 'contactos')
	 * @param  string  $prefix Prefixo opcional (ex.: 'EC')
	 * @param  int     $pad    Dígitos (ex.: 4 → 0001)
	 */
	public static function next(string $key, string $prefix = '', int $pad = 4): string
	{
		return DB::transaction(function () use ($key, $prefix, $pad) {
			$row = DB::table('sequences')->lockForUpdate()->where('key', $key)->first();

			if (!$row) {
				// se não existir sequência, inicia com o maior numero da própria tabela
				$table = Str::snake($key); // ex.: 'contactos'
				if (!DB::getSchemaBuilder()->hasTable($table)) {
					$table = 'entidades'; // fallback seguro
				}

				$lastNumber = DB::table($table)->max('numero');
				$initialValue = $lastNumber ? ((int) $lastNumber + 1) : 1;

				DB::table('sequences')->insert([
					'key'        => $key,
					'next'       => $initialValue + 1,
					'created_at' => now(),
					'updated_at' => now(),
				]);

				$val = $initialValue;
			} else {
				$val = $row->next;
				DB::table('sequences')
					->where('key', $key)
					->update(['next' => $val + 1, 'updated_at' => now()]);
			}

			// se o campo é bigint puro, devolve apenas número, não prefixo nem ano
			if ($prefix === '' && !Str::contains($key, ['sales', 'purchase', 'propostas'])) {
				return (string) $val;
			}

			// formato prefixado opcional: EC-2025-0001
			$num = str_pad((string) $val, $pad, '0', STR_PAD_LEFT);
			return $prefix
				? sprintf('%s-%d-%s', $prefix, now()->year, $num)
				: $num;
		});
	}
}
