<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('iva', function (Blueprint $table) {
			// só adiciona o índice se ainda não existir
			if (!Schema::hasColumn('iva', 'nome')) {
				throw new \RuntimeException("A coluna 'nome' não existe na tabela iva.");
			}

			$table->unique('nome', 'iva_nome_unique');
		});
	}

	public function down(): void
	{
		Schema::table('iva', function (Blueprint $table) {
			$table->dropUnique('iva_nome_unique');
		});
	}
};
