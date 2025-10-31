<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('fornecedor_faturas', function (Blueprint $table) {
			$table->id();
			$table->string('numero')->unique();
			$table->date('data_fatura');
			$table->date('data_vencimento')->nullable();

			// Relações
			$table->foreignId('fornecedor_id')
				->constrained('entidades')
				->cascadeOnUpdate()
				->restrictOnDelete();

			$table->foreignId('encomenda_fornecedor_id')
				->nullable()
				->constrained('encomendas_fornecedores')
				->cascadeOnUpdate()
				->nullOnDelete();

			$table->decimal('valor_total', 12, 2)->default(0);

			// Ficheiros (armazenar caminhos em storage privado)
			$table->string('documento_path')->nullable();
			$table->string('comprovativo_path')->nullable();

			$table->enum('estado', ['pendente', 'paga'])->default('pendente');

			// Marcação de quando foi enviado comprovativo
			$table->timestamp('comprovativo_enviado_em')->nullable();

			$table->timestamps();
			$table->softDeletes();

			$table->index(['fornecedor_id', 'data_fatura']);
			$table->index(['estado']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('fornecedor_faturas');
	}
};
