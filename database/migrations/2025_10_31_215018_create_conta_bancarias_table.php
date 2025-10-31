<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('conta_bancarias', function (Blueprint $table) {
			$table->id();
			$table->string('banco')->nullable();
			$table->string('titular')->nullable();
			$table->string('iban')->unique();
			$table->string('swift_bic')->nullable();
			$table->string('numero_conta')->nullable();
			$table->decimal('saldo_abertura', 14, 2)->default(0);
			$table->boolean('ativo')->default(true);
			$table->text('notas')->nullable();

			$table->timestamps();
			$table->softDeletes();
			$table->index(['ativo', 'banco']);
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('conta_bancarias');
	}
};
