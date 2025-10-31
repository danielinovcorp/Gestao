<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SequenceService
{
	/**
	 * Gera o próximo número sequencial formatado.
	 *
	 * @param string $key     Nome da sequência (ex: 'sales_orders', 'purchase_orders')
	 * @param string $prefix  Prefixo da série (ex: 'EC', 'EF')
	 * @param int    $pad     Quantos dígitos preencher (ex: 4 -> 0001)
	 */
	public function next(string $key, string $prefix = '', int $pad = 4): string
	{
		return DB::transaction(function () use ($key, $prefix, $pad) {
			$row = DB::table('sequences')->lockForUpdate()->where('key', $key)->first();

			if (!$row) {
				DB::table('sequences')->insert([
					'key' => $key,
					'next' => 1,
					'created_at' => now(),
					'updated_at' => now(),
				]);
				$val = 1;
			} else {
				$val = $row->next;
				DB::table('sequences')
					->where('key', $key)
					->update(['next' => $val + 1, 'updated_at' => now()]);
			}

			// formato: EC-2025-0001
			$num = str_pad((string) $val, $pad, '0', STR_PAD_LEFT);
			return $prefix
				? sprintf('%s-%d-%s', $prefix, now()->year, $num)
				: $num;
		});
	}
}
