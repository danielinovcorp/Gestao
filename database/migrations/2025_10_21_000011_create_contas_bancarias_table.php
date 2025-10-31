<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('contas_bancarias', function (Blueprint $table) {
			$table->id();
			$table->string('banco');
			$table->text('iban_enc');   // cifrado via cast
			$table->text('swift_enc')->nullable();
			$table->text('titular_enc')->nullable();
			$table->timestamps();
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('contas_bancarias');
	}
};
