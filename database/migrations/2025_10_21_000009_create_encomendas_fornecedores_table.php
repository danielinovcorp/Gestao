<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('encomendas_fornecedores', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('numero')->unique();
			$table->date('data_encomenda');
			$table->foreignId('fornecedor_id')->constrained('entidades');
			$table->enum('estado', ['rascunho', 'fechado'])->default('rascunho');
			$table->decimal('total', 14, 2)->default(0);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['fornecedor_id', 'estado', 'data_encomenda'], 'enc_forn_idx');
		});

		Schema::create('encomenda_fornecedor_linhas', function (Blueprint $table) {
			$table->id();
			$table->foreignId('encomenda_fornecedor_id')->constrained('encomendas_fornecedores')->cascadeOnDelete();
			$table->foreignId('artigo_id')->nullable()->constrained('artigos');
			$table->string('descricao');
			$table->decimal('qtd', 12, 3);
			$table->decimal('preco', 12, 2);
			$table->foreignId('iva_id')->nullable()->constrained('iva');
			$table->decimal('total_linha', 14, 2);
			$table->timestamps();
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('encomenda_fornecedor_linhas');
		Schema::dropIfExists('encomendas_fornecedores');
	}
};
