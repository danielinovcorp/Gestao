<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('entidades', function (Blueprint $table) {
			// se preferir enum:
			// $table->enum('tipo', ['cliente','fornecedor','both'])->default('cliente')->after('nome');
			$table->string('tipo', 20)->default('cliente')->after('nome')->index();
		});
	}

	public function down(): void
	{
		Schema::table('entidades', function (Blueprint $table) {
			$table->dropColumn('tipo');
		});
	}
};
