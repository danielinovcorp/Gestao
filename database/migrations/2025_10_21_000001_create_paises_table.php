<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		Schema::create('paises', function (Blueprint $table) {
			$table->id();
			$table->string('iso', 2)->unique();
			$table->string('nome');
			$table->timestamps();
		});
	}
	public function down(): void {
		Schema::dropIfExists('paises');
	}
};
