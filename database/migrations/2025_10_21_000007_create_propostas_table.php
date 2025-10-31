<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('propostas', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('numero')->unique();
			$table->date('data_proposta');      // data em que fica "fechado"
			$table->date('validade')->nullable(); // default 30 dias após data_proposta (lógica na app)
			$table->foreignId('cliente_id')->constrained('entidades');
			$table->enum('estado', ['rascunho', 'fechado'])->default('rascunho');
			$table->decimal('total', 14, 2)->default(0);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['cliente_id', 'estado', 'data_proposta'], 'prop_idx');
		});

		Schema::create('proposta_linhas', function (Blueprint $table) {
			$table->id();
			$table->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
			$table->foreignId('artigo_id')->nullable()->constrained('artigos');
			$table->string('descricao');
			$table->decimal('qtd', 12, 3);
			$table->decimal('preco', 12, 2);
			$table->foreignId('fornecedor_id')->nullable()->constrained('entidades'); // fornecedor por linha
			$table->decimal('preco_custo', 12, 2)->nullable();
			$table->foreignId('iva_id')->nullable()->constrained('iva');
			$table->decimal('total_linha', 14, 2);
			$table->timestamps();
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('proposta_linhas');
		Schema::dropIfExists('propostas');
	}
};
