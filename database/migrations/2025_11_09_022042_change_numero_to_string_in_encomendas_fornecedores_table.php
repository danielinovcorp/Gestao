<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up()
	{
		Schema::table('encomendas_fornecedores', function (Blueprint $table) {
			$table->string('numero', 20)->change();
		});
	}

	public function down()
	{
		Schema::table('encomendas_fornecedores', function (Blueprint $table) {
			$table->bigInteger('numero')->unsigned()->change();
		});
	}
};
