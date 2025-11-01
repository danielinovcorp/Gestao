<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('calendario_tipos', function (Blueprint $table) {
			$table->id();
			$table->string('nome')->unique();
			$table->string('cor_hex', 10)->nullable();
			$table->enum('estado', ['ativo', 'inativo'])->default('ativo');
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('calendario_tipos');
	}
};
