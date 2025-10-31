<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('cliente_movimentos', function (Blueprint $table) {
			$table->id();
			$table->foreignId('cliente_id')->constrained('entidades')->cascadeOnUpdate()->restrictOnDelete();
			$table->date('data');
			$table->string('descricao')->nullable();
			$table->enum('documento_tipo', ['fatura', 'recibo', 'nota_credito', 'ajuste'])->nullable();
			$table->string('documento_numero')->nullable();
			$table->decimal('debito', 14, 2)->default(0); // valor que o cliente nos deve
			$table->decimal('credito', 14, 2)->default(0); // valor que recebemos
			$table->timestamps();
			$table->index(['cliente_id', 'data']);
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('cliente_movimentos');
	}
};
