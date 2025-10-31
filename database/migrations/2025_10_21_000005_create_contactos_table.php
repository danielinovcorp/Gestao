<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('contactos', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('numero')->unique();
			$table->foreignId('entidade_id')->constrained('entidades')->cascadeOnDelete();
			$table->string('nome');
			$table->string('apelido')->nullable();
			$table->foreignId('funcao_id')->nullable()->constrained('contacto_funcoes');

			// contactos (cifrados)
			$table->text('telefone_enc')->nullable();
			$table->text('telemovel_enc')->nullable();
			$table->text('email_enc')->nullable();

			$table->enum('consentimento_rgpd', ['sim', 'nao'])->default('nao');
			$table->text('observacoes')->nullable();
			$table->enum('estado', ['ativo', 'inativo'])->default('ativo');

			$table->timestamps();
			$table->softDeletes();

			$table->index(['entidade_id', 'estado']);
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('contactos');
	}
};
