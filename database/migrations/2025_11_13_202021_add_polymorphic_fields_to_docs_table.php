<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docs', function (Blueprint $table) {
            // === REMOVER COLUNAS ANTIGAS (se existirem) ===
            $oldColumns = ['categoria', 'titulo', 'path_private', 'hash', 'owner_id', 'visibilidade', 'cifrado'];
            foreach ($oldColumns as $col) {
                if (Schema::hasColumn('docs', $col)) {
                    if ($col === 'owner_id') {
                        $table->dropForeign(['owner_id']);
                    }
                    $table->dropColumn($col);
                }
            }

            // === ADICIONAR CAMPOS POLIMÓRFICOS ===
            if (!Schema::hasColumn('docs', 'documentable_type')) {
                $table->string('documentable_type')->nullable()->after('id');
            }
            if (!Schema::hasColumn('docs', 'documentable_id')) {
                $table->unsignedBigInteger('documentable_id')->nullable()->after('documentable_type');
                $table->index(['documentable_type', 'documentable_id']);
            }

            // === uploaded_by ===
            if (!Schema::hasColumn('docs', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete()->after('documentable_id');
            }

            // === CAMPOS PRINCIPAIS (só adiciona se não existirem) ===
            $fields = [
                'title' => fn() => $table->string('title'),
                'original_name' => fn() => $table->string('original_name'),
                'ext' => fn() => $table->string('ext')->nullable(),
                'mime' => fn() => $table->string('mime'),
                'size' => fn() => $table->bigInteger('size'),
                'disk' => fn() => $table->string('disk')->default('private'),
                'path' => fn() => $table->string('path'),
                'sha256' => fn() => $table->string('sha256')->unique()->nullable(),
                'valid_until' => fn() => $table->date('valid_until')->nullable(),
            ];

            foreach ($fields as $col => $callback) {
                if (!Schema::hasColumn('docs', $col)) {
                    $callback();
                }
            }

            // === tags e notes (garantir tipo correto) ===
            if (Schema::hasColumn('docs', 'tags')) {
                // Se existir, mas não for JSON, mudar
                $type = Schema::getColumnType('docs', 'tags');
                if ($type !== 'json') {
                    $table->json('tags')->nullable()->change();
                }
            } else {
                $table->json('tags')->nullable()->after('sha256');
            }

            if (Schema::hasColumn('docs', 'notes')) {
                $type = Schema::getColumnType('docs', 'notes');
                if (!in_array($type, ['text', 'longtext'])) {
                    $table->text('notes')->nullable()->change();
                }
            } else {
                $table->text('notes')->nullable()->after('tags');
            }

            // === ÍNDICES ===
            $indexes = [
                ['uploaded_by'],
                ['created_at'],
                ['sha256'],
            ];
            foreach ($indexes as $cols) {
                $indexName = 'docs_' . implode('_', $cols) . '_index';
                if (!collect(DB::select("SHOW INDEXES FROM docs WHERE Key_name = ?", [$indexName]))->count()) {
                    $table->index($cols, $indexName);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('docs', function (Blueprint $table) {
            $table->dropIndex(['documentable_type', 'documentable_id']);
            $table->dropColumn(['documentable_type', 'documentable_id']);
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn('uploaded_by');

            $table->dropColumn([
                'title', 'original_name', 'ext', 'mime', 'size',
                'disk', 'path', 'sha256', 'valid_until'
            ]);

            // Recria colunas antigas
            $table->string('categoria')->nullable();
            $table->string('titulo');
            $table->string('path_private');
            $table->string('hash')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users');
            $table->string('visibilidade')->default('privado');
            $table->boolean('cifrado')->default(true);
        });
    }
};