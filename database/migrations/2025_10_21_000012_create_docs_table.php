<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('docs', function (Blueprint $table) {
			$table->id();
			$table->string('categoria')->nullable();
			$table->string('titulo');
			$table->string('path_private'); // fora da public_html
			$table->string('hash')->nullable();
			$table->foreignId('owner_id')->nullable()->constrained('users');
			$table->string('visibilidade')->default('privado');
			$table->json('tags')->nullable();
			$table->boolean('cifrado')->default(true);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['categoria', 'owner_id']);
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('docs');
	}
};
