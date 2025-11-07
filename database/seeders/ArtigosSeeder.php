<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArtigosSeeder extends Seeder
{
	public function run(): void
	{
		// Helper para obter o ID do IVA pela percentagem (0, 6, 13, 23...)
		$ivaId = function (int $percent) {
			$row = DB::table('iva')
				->select('id')
				->where('taxa', $percent) // ✅ Apenas verifica a coluna 'taxa'
				->when(
					DB::getSchemaBuilder()->hasColumn('iva', 'estado'),
					fn($q) => $q->where('estado', 'ativo')
				)
				->first();

			return $row?->id ?? null;
		};

		$now = now();

		$items = [
			// Produtos físicos (23%)
			[
				'referencia'  => 'MON24',
				'nome'        => 'Monitor 24" LED',
				'descricao'   => 'Resolução 1920x1080, painel IPS, 75Hz.',
				'preco'       => 120.00,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'TECMEC',
				'nome'        => 'Teclado Mecânico',
				'descricao'   => 'Switches azuis, layout PT, iluminação RGB.',
				'preco'       => 65.90,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'RATOPT',
				'nome'        => 'Rato Óptico',
				'descricao'   => '1600 DPI, 3 botões, USB.',
				'preco'       => 12.50,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'CABHDMI2M',
				'nome'        => 'Cabo HDMI 2m',
				'descricao'   => 'HDMI 2.1, 8K, 2 metros.',
				'preco'       => 8.90,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'SSD1TB',
				'nome'        => 'SSD 1TB NVMe',
				'descricao'   => 'Leituras até 3500MB/s.',
				'preco'       => 89.00,
				'iva_percent' => 23,
			],

			// Serviços (23% ou 6/13 conforme negócio; aqui deixo 23%)
			[
				'referencia'  => 'SERVCONS',
				'nome'        => 'Hora de Consultoria',
				'descricao'   => 'Consultoria técnica presencial/remota (1h).',
				'preco'       => 45.00,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'SERVINST',
				'nome'        => 'Instalação de Equipamento',
				'descricao'   => 'Deslocação e instalação de equipamento.',
				'preco'       => 60.00,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'SERVSUPM',
				'nome'        => 'Suporte Mensal',
				'descricao'   => 'Suporte remoto mensal (pacote básico).',
				'preco'       => 75.00,
				'iva_percent' => 23,
			],

			// Licenças/Software (muitos negócios usam 23%)
			[
				'referencia'  => 'LICANUAL',
				'nome'        => 'Licença Software – Anual',
				'descricao'   => 'Subscrição anual do software X.',
				'preco'       => 240.00,
				'iva_percent' => 23,
			],
			[
				'referencia'  => 'LICMENSAL',
				'nome'        => 'Licença Software – Mensal',
				'descricao'   => 'Subscrição mensal do software X.',
				'preco'       => 24.00,
				'iva_percent' => 23,
			],

			// Exemplo com IVA reduzido (6%) — se existir no teu seeder de IVA
			[
				'referencia'  => 'LIVROFISC',
				'nome'        => 'Livro Técnico (Física)',
				'descricao'   => 'Material didático. Exemplo com IVA 6%.',
				'preco'       => 19.90,
				'iva_percent' => 6,
			],
		];

		foreach ($items as $it) {
			DB::table('artigos')->updateOrInsert(
				['referencia' => $it['referencia']], // idempotente pelo código
				[
					'nome'        => $it['nome'],
					'descricao'   => $it['descricao'],
					'preco'       => $it['preco'],
					'iva_id'      => $ivaId($it['iva_percent']),
					'foto_path'   => null,      // mantém null; podes subir manualmente
					'observacoes' => null,
					'estado'      => 'ativo',
					'updated_at'  => $now,
					'created_at'  => $now,
				]
			);
		}
	}
}
