<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('calendar_events', function (Blueprint $table) {
			$table->id();
			$table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
			$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
			$table->foreignId('entidade_id')->nullable()->constrained('entidades')->nullOnDelete();
			$table->foreignId('calendar_type_id')->nullable()->constrained('calendar_types')->nullOnDelete();
			$table->foreignId('calendar_action_id')->nullable()->constrained('calendar_actions')->nullOnDelete();

			$table->dateTime('start');
			$table->unsignedInteger('duration_minutes')->default(60);
			$table->dateTime('end');

			$table->string('descricao', 1000)->nullable();
			$table->enum('estado', ['agendado', 'concluido', 'cancelado'])->default('agendado');

			$table->boolean('partilha_global')->default(false);
			$table->boolean('conhecimento_global')->default(false);

			$table->timestamps();
			$table->softDeletes();
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('calendar_events');
	}
};
