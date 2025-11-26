<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private $tables = [
        'entidades', 'contactos', 'artigos', 'propostas', 'proposta_linhas',
        'encomendas_clientes', 'encomenda_cliente_linhas',
        'encomendas_fornecedores', 'encomenda_fornecedor_linhas',
        'faturas_fornecedores', 'ordens_trabalho', 'docs',
        'iva', 'paises', 'contacto_funcoes', 'calendario_tipos',
        'calendario_acoes', 'empresa', 'conta_bancarias'
    ];

    public function up(): void
    {
        // 1. Primeiro: adiciona tenant_id em todas as tabelas (sem FK)
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function ($table) use ($tableName) {
                    if (!collect(DB::select("SHOW COLUMNS FROM `{$tableName}` LIKE 'tenant_id'"))->isNotEmpty()) {
                        $table->string('tenant_id')->nullable()->after('id');
                        $table->index('tenant_id');
                    }
                });
            }
        }

        // 2. Depois: cria um tenant padrão (sem owner_id obrigatório)
        $tenantId = (string) Str::ulid();

        DB::table('tenants')->insert([
            'id'         => $tenantId,
            'owner_id'   => null,  // ← pode ser null
            'name'       => 'Minha Empresa',
            'slug'       => 'minha-empresa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Preenche todos os registros com este tenant
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)
                    ->whereNull('tenant_id')
                    ->orWhere('tenant_id', '')
                    ->update(['tenant_id' => $tenantId]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function ($table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};