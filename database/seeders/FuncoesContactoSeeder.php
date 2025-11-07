<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuncoesContactoSeeder extends Seeder
{
    public function run(): void
    {
        $itens = [
            ['nome' => 'Direção',   'estado' => 'ativo'],
            ['nome' => 'Compras',   'estado' => 'ativo'],
            ['nome' => 'Financeiro','estado' => 'ativo'],
            ['nome' => 'Técnico',   'estado' => 'ativo'],
            ['nome' => 'Comercial', 'estado' => 'ativo'],
        ];

        foreach ($itens as $i) {
            DB::table('funcoes_contacto')->updateOrInsert(
                ['nome' => $i['nome']],
                ['estado' => $i['estado'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
