<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('ordens_trabalho', function (Blueprint $table) {
			$table->id();
			$table->string('numero')->unique(); // ex: OT0001
			$table->foreignId('cliente_id')->constrained('entidades'); // mesma tabela de Clientes/Fornecedores
			$table->foreignId('responsavel_id')->constrained('users');
			$table->text('descricao');
			$table->date('data_inicio')->nullable();
			$table->date('data_fim')->nullable();
			$table->string('estado', 20)->default('pendente'); // pendente|em_execucao|concluida|cancelada
			$table->text('observacoes')->nullable();
			$table->timestamps();

			$table->index(['cliente_id', 'responsavel_id', 'estado']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('ordens_trabalho');
	}
};
