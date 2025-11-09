<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up()
	{
		Schema::table('ordens_trabalho', function (Blueprint $table) {
			// Remover responsavel_id se existir
			if (Schema::hasColumn('ordens_trabalho', 'responsavel_id')) {
				$table->dropForeign(['responsavel_id']);
				$table->dropColumn('responsavel_id');
			}

			// Adicionar novas colunas
			$table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media')->after('estado');
			$table->foreignId('servico_id')->constrained('artigos')->after('cliente_id');
		});
	}

	public function down()
	{
		Schema::table('ordens_trabalho', function (Blueprint $table) {
			$table->dropForeign(['servico_id']);
			$table->dropColumn('servico_id');
			$table->dropColumn('prioridade');
		});
	}
};
