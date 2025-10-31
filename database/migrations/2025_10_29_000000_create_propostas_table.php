<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		if (Schema::hasTable('propostas')) {
			return; // já existe, não recria
		}

		Schema::create('propostas', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('numero')->unique();
			$table->foreignId('cliente_id')->constrained('entidades')->cascadeOnUpdate();
			$table->date('data_proposta')->nullable();
			$table->date('validade')->nullable();
			$table->enum('estado', ['rascunho', 'fechado'])->default('rascunho');
			$table->decimal('valor_total', 12, 2)->default(0);
			$table->json('observacoes')->nullable();
			$table->timestamps();
		});
	}


	public function down(): void
	{
		Schema::dropIfExists('propostas');
	}
};
