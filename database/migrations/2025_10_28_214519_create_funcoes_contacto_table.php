<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('funcoes_contacto', function (Blueprint $t) {
			$t->id();
			$t->string('nome')->unique();
			$t->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('funcoes_contacto');
	}
};
