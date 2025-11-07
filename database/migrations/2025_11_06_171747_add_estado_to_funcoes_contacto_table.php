<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('funcoes_contacto', function (Blueprint $table) {
			if (!Schema::hasColumn('funcoes_contacto', 'estado')) {
				$table->enum('estado', ['ativo', 'inativo'])->default('ativo')->after('nome');
			}
			if (!Schema::hasColumn('funcoes_contacto', 'created_at')) {
				$table->timestamps();
			}
		});
	}

	public function down(): void
	{
		Schema::table('funcoes_contacto', function (Blueprint $table) {
			if (Schema::hasColumn('funcoes_contacto', 'estado')) {
				$table->dropColumn('estado');
			}
			if (Schema::hasColumn('funcoes_contacto', 'created_at')) {
				$table->dropTimestamps();
			}
		});
	}
};
