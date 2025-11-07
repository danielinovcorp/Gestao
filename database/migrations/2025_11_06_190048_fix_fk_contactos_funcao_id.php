<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('contactos', function (Blueprint $table) {
			// solta a FK antiga (mesmo sem saber o nome exato)
			if (Schema::hasColumn('contactos', 'funcao_id')) {
				try {
					$table->dropForeign(['funcao_id']);
				} catch (\Throwable $e) {
					// ignora se já não existir
				}
				// cria a FK correta para funcoes_contacto(id)
				$table->foreign('funcao_id')
					->references('id')->on('funcoes_contacto')
					->nullOnDelete()   // se apagares a função, seta NULL
					->cascadeOnUpdate();
			}
		});
	}

	public function down(): void
	{
		Schema::table('contactos', function (Blueprint $table) {
			try {
				$table->dropForeign(['funcao_id']);
			} catch (\Throwable $e) {
			}
			// (opcional) volta a FK antiga se quiseres — normalmente não precisa
		});
	}
};
