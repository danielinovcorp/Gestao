<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('artigos', function (Blueprint $table) {
			$table->id();
			$table->string('referencia')->unique();
			$table->string('nome');
			$table->text('descricao')->nullable();
			$table->decimal('preco', 12, 2)->default(0);
			$table->foreignId('iva_id')->nullable()->constrained('iva');
			$table->string('foto_path')->nullable(); // storage/app/private
			$table->text('observacoes')->nullable();
			$table->enum('estado', ['ativo', 'inativo'])->default('ativo');
			$table->timestamps();
			$table->softDeletes();
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('artigos');
	}
};
