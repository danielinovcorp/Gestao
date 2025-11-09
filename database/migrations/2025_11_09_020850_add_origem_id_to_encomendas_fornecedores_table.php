<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up()
	{
		Schema::table('encomendas_fornecedores', function (Blueprint $table) {
			$table->foreignId('origem_id')
				->nullable()
				->after('fornecedor_id')
				->constrained('encomendas_clientes')
				->onDelete('set null');
		});
	}

	public function down()
	{
		Schema::table('encomendas_fornecedores', function (Blueprint $table) {
			$table->dropForeign(['origem_id']);
			$table->dropColumn('origem_id');
		});
	}
};
