<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up()
	{
		Schema::table('sales_orders', function (Blueprint $table) {
			$table->foreignId('proposta_id')->nullable()->constrained('propostas')->after('cliente_id');
		});
	}

	public function down()
	{
		Schema::table('sales_orders', function (Blueprint $table) {
			$table->dropForeign(['proposta_id']);
			$table->dropColumn('proposta_id');
		});
	}
};
