<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Leitura do estado atual da tabela
        $hasNumero    = Schema::hasColumn('contactos', 'numero');
        $hasApelido   = Schema::hasColumn('contactos', 'apelido');
        $hasFuncaoId  = Schema::hasColumn('contactos', 'funcao_id');
        $hasTelemovel = Schema::hasColumn('contactos', 'telemovel');
        $hasTelefone  = Schema::hasColumn('contactos', 'telefone');
        $hasRgpd      = Schema::hasColumn('contactos', 'consentimento_rgpd');
        $hasEstado    = Schema::hasColumn('contactos', 'estado');
        $hasNome      = Schema::hasColumn('contactos', 'nome');
        $hasEmail     = Schema::hasColumn('contactos', 'email');

        Schema::table('contactos', function (Blueprint $t) use (
            $hasNumero, $hasApelido, $hasFuncaoId, $hasTelemovel, $hasTelefone,
            $hasRgpd, $hasEstado, $hasNome, $hasEmail
        ) {
            // numero
            if (!$hasNumero) {
                // se "id" existe, colocamos depois dele; senão, cria no fim
                try { $t->bigInteger('numero')->nullable()->index()->after('id'); }
                catch (\Throwable $e) { $t->bigInteger('numero')->nullable()->index(); }
            }

            // apelido (depois de nome se existir)
            if (!$hasApelido) {
                $col = $t->string('apelido', 255)->nullable();
                if ($hasNome) $col->after('nome');
            }

            // funcao_id (depois de apelido se existir)
            if (!$hasFuncaoId) {
                $col = $t->unsignedBigInteger('funcao_id')->nullable();
                if ($hasApelido) $col->after('apelido');

                // FK (só cria se a tabela alvo existir em tempo de migração)
                try {
                    $t->foreign('funcao_id')->references('id')->on('funcoes_contacto')->nullOnDelete();
                } catch (\Throwable $e) {
                    // se a tabela ainda não existe, podes criar a FK numa migration posterior
                }
            }

            // telemovel (depois de telefone se existir)
            if (!$hasTelemovel) {
                $col = $t->string('telemovel', 40)->nullable();
                if ($hasTelefone) $col->after('telefone');
            }

            // RGPD (depois de email se existir)
            if (!$hasRgpd) {
                $col = $t->enum('consentimento_rgpd', ['sim', 'nao'])->default('nao');
                if ($hasEmail) $col->after('email');
            }

            // estado (depois de consentimento_rgpd se existir)
            if (!$hasEstado) {
                // aqui não precisamos do after; se existir o RGPD, já veio antes
                $t->enum('estado', ['ativo', 'inativo'])->default('ativo');
            }
        });
    }

    public function down(): void
    {
        $hasNumero    = Schema::hasColumn('contactos', 'numero');
        $hasApelido   = Schema::hasColumn('contactos', 'apelido');
        $hasFuncaoId  = Schema::hasColumn('contactos', 'funcao_id');
        $hasTelemovel = Schema::hasColumn('contactos', 'telemovel');
        $hasRgpd      = Schema::hasColumn('contactos', 'consentimento_rgpd');
        $hasEstado    = Schema::hasColumn('contactos', 'estado');

        Schema::table('contactos', function (Blueprint $t) use (
            $hasNumero, $hasApelido, $hasFuncaoId, $hasTelemovel, $hasRgpd, $hasEstado
        ) {
            if ($hasFuncaoId) {
                // remove FK se existir
                try { $t->dropForeign(['funcao_id']); } catch (\Throwable $e) {}
                $t->dropColumn('funcao_id');
            }
            if ($hasNumero)    $t->dropColumn('numero');
            if ($hasApelido)   $t->dropColumn('apelido');
            if ($hasTelemovel) $t->dropColumn('telemovel');
            if ($hasRgpd)      $t->dropColumn('consentimento_rgpd');
            if ($hasEstado)    $t->dropColumn('estado');
        });
    }
};
