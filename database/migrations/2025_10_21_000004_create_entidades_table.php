<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('entidades', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('numero')->unique(); // incremental gerido por serviço
			$table->boolean('is_cliente')->default(false);
			$table->boolean('is_fornecedor')->default(false);

			// NIF cifrado + hash único
			$table->text('nif_enc');                 // valor cifrado (cast no Model)
			$table->char('nif_hash', 64)->unique();  // SHA-256 do NIF normalizado

			$table->string('nome');
			$table->string('morada')->nullable();
			$table->string('codigo_postal', 8)->nullable(); // XXXX-XXX
			$table->string('localidade')->nullable();
			$table->foreignId('pais_id')->nullable()->constrained('paises');

			// contactos (cifrados)
			$table->text('telefone_enc')->nullable();
			$table->text('telemovel_enc')->nullable();
			$table->string('website')->nullable();
			$table->text('email_enc')->nullable();

			$table->enum('consentimento_rgpd', ['sim', 'nao'])->default('nao');
			$table->text('observacoes')->nullable();
			$table->enum('estado', ['ativo', 'inativo'])->default('ativo');

			$table->timestamps();
			$table->softDeletes();

			$table->index(['is_cliente', 'is_fornecedor']);
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('entidades');
	}
};
