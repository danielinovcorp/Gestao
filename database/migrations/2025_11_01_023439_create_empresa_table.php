<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('empresa', function (Blueprint $table) {
			$table->id();
			$table->string('nome')->nullable();
			$table->string('morada')->nullable();
			$table->string('codigo_postal', 20)->nullable();
			$table->string('localidade')->nullable();
			$table->string('nif', 32)->nullable();
			$table->string('logo_path')->nullable(); // ficheiro no disk 'private'
			$table->timestamps();
		});

		// cria o registo único (id=1)
		DB::table('empresa')->insert([
			'id' => 1,
			'created_at' => now(),
			'updated_at' => now(),
		]);
	}

	public function down(): void
	{
		Schema::dropIfExists('empresa');
	}
};
