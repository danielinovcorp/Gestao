<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('funcoes_contacto', function (Blueprint $t) {
			$t->id();
			$t->string('nome')->unique();
			$t->enum('estado', ['ativo', 'inativo'])->default('ativo'); // ADICIONAR ESTADO
			$t->timestamps();
		});

		// Dados iniciais
		DB::table('funcoes_contacto')->insert([
			['nome' => 'Diretor Geral', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Gestor de Contas', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Comercial', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Financeiro', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Suporte Técnico', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Administrativo', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Recursos Humanos', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
			['nome' => 'Marketing', 'estado' => 'ativo', 'created_at' => now(), 'updated_at' => now()],
		]);
	}

	public function down(): void
	{
		Schema::dropIfExists('funcoes_contacto');
	}
};
