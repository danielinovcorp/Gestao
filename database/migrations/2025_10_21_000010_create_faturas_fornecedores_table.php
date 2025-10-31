<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('faturas_fornecedores', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('numero')->unique();
			$table->date('data_fatura');
			$table->date('data_vencimento')->nullable();
			$table->foreignId('fornecedor_id')->constrained('entidades');
			$table->foreignId('encomenda_fornecedor_id')->nullable()->constrained('encomendas_fornecedores');
			$table->decimal('valor_total', 14, 2);
			$table->string('documento_path')->nullable();     // storage/app/private
			$table->string('comprovativo_path')->nullable();  // storage/app/private
			$table->enum('estado', ['pendente', 'paga'])->default('pendente');
			$table->timestamps();
			$table->softDeletes();

			$table->index(['fornecedor_id', 'estado', 'data_fatura']);
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('faturas_fornecedores');
	}
};
