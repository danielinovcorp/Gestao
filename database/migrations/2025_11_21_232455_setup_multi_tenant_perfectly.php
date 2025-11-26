<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
	private $tables = [
		'entidades',
		'contactos',
		'artigos',
		'propostas',
		'proposta_linhas',
		'encomendas_clientes',
		'encomenda_cliente_linhas',
		'encomendas_fornecedores',
		'encomenda_fornecedor_linhas',
		'faturas_fornecedores',
		'ordens_trabalho',
		'docs',
		'iva',
		'paises',
		'contacto_funcoes',
		'calendario_tipos',
		'calendario_acoes',
		'empresa',
		'conta_bancarias'
	];

	public function up(): void
	{
		// 1. Adiciona tenant_id em todas as tabelas
		foreach ($this->tables as $tableName) {
			if (Schema::hasTable($tableName)) {
				Schema::table($tableName, function ($table) use ($tableName) {
					if (!collect(DB::select("SHOW COLUMNS FROM `{$tableName}` LIKE 'tenant_id'"))->isNotEmpty()) {
						$table->string('tenant_id')->nullable()->after('id');
						$table->index('tenant_id');
					}
				});
			}
		}

		// 2. Cria tenant padrão se ainda não existir
		if (DB::table('tenants')->count() === 0) {
			DB::table('tenants')->insert([
				'id'         => (string) Str::ulid(),
				'owner_id'   => null,
				'name'       => 'Minha Empresa',
				'slug'       => 'minha-empresa-' . Str::random(6),
				'created_at' => now(),
				'updated_at' => now(),
			]);
		}

		// 3. Pega o ID do primeiro tenant
		$defaultTenantId = DB::table('tenants')->value('id');

		// 4. Preenche todos os registros
		foreach ($this->tables as $tableName) {
			if (Schema::hasTable($tableName)) {
				DB::table($tableName)
					->whereNull('tenant_id')
					->orWhere('tenant_id', '')
					->update(['tenant_id' => $defaultTenantId]);
			}
		}
	}

	public function down(): void
	{
		foreach ($this->tables as $tableName) {
			if (Schema::hasTable($tableName)) {
				Schema::table($tableName, function ($table) {
					$table->dropIndex(['tenant_id']);
					$table->dropColumn('tenant_id');
				});
			}
		}
	}
};
