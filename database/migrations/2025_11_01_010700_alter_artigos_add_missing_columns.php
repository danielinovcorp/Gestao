<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('artigos', function (Blueprint $table) {
			if (!Schema::hasColumn('artigos', 'descricao'))   $table->text('descricao')->nullable()->after('nome');
			if (!Schema::hasColumn('artigos', 'preco'))       $table->decimal('preco', 12, 2)->default(0)->after('descricao');
			if (!Schema::hasColumn('artigos', 'iva_id'))      $table->foreignId('iva_id')->nullable()->constrained('iva')->nullOnDelete()->after('preco');
			if (!Schema::hasColumn('artigos', 'foto_path'))   $table->string('foto_path')->nullable()->after('iva_id');
			if (!Schema::hasColumn('artigos', 'observacoes')) $table->text('observacoes')->nullable()->after('foto_path');
			if (!Schema::hasColumn('artigos', 'estado'))      $table->enum('estado', ['ativo', 'inativo'])->default('ativo')->after('observacoes');
			if (!Schema::hasColumn('artigos', 'created_at'))  $table->timestamps();
			if (!Schema::hasColumn('artigos', 'deleted_at'))  $table->softDeletes();
		});
	}
	public function down(): void
	{
		Schema::table('artigos', function (Blueprint $table) {
			// reverte só se existirem (opcional)
		});
	}
};
