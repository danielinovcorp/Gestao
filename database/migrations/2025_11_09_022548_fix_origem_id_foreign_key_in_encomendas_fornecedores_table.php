<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('encomendas_fornecedores', function (Blueprint $table) {
            // Remove a FK antiga
            $table->dropForeign(['origem_id']);

            // Recria apontando para sales_orders
            $table->foreign('origem_id')
                  ->references('id')
                  ->on('sales_orders')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('encomendas_fornecedores', function (Blueprint $table) {
            $table->dropForeign(['origem_id']);
            $table->foreign('origem_id')
                  ->references('id')
                  ->on('encomendas_clientes')
                  ->onDelete('set null');
        });
    }
};