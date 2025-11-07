<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('sales_orders')) {
			Schema::create('sales_orders', function (Blueprint $t) {
				$t->id();
				$t->string('numero')->nullable();
				$t->foreignId('cliente_id')->constrained('entidades')->cascadeOnDelete();
				$t->enum('estado', ['rascunho', 'fechado'])->default('rascunho');
				$t->datetime('data_proposta')->nullable();
				$t->date('validade')->nullable();
				$t->decimal('total', 12, 2)->default(0);
				$t->timestamps();
			});
		}

		if (!Schema::hasTable('sales_order_lines')) {
			Schema::create('sales_order_lines', function (Blueprint $t) {
				$t->id();
				$t->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
				$t->foreignId('artigo_id')->constrained('artigos');
				$t->string('descricao')->nullable();
				$t->decimal('quantidade', 12, 3);
				$t->decimal('preco', 12, 2);
				$t->foreignId('iva_id')->nullable()->constrained('iva');
				$t->foreignId('fornecedor_id')->nullable()->constrained('entidades');
				$t->decimal('total', 12, 2);
				$t->timestamps();
			});
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('sales_order_lines');
		Schema::dropIfExists('sales_orders');
	}
};
