<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		if (! Schema::hasTable('docs')) { // opcional: proteção anti-duplicação
			Schema::create('docs', function (Blueprint $t) {
				$t->id();

				// relação polimórfica: Entity, Proposal, Order, etc. (opcional)
				$t->nullableMorphs('documentable'); // documentable_id, documentable_type

				// metadados (encrypted via casts no Model)
				$t->string('title')->nullable();
				$t->string('original_name');          // nome original do upload
				$t->string('ext', 16)->nullable();    // extensão (pdf, jpg, ...)
				$t->string('mime', 191)->nullable();  // content-type detectado
				$t->unsignedBigInteger('size');       // bytes
				$t->json('tags')->nullable();         // ["contrato","rgpd"] etc.
				$t->text('notes')->nullable();        // observações

				// armazenamento
				$t->string('disk')->default('private');   // filesystems.php
				$t->string('path');                       // ex: documents/2025/10/uuid.pdf
				$t->string('sha256', 64)->nullable();     // integridade/deduplicação opcional

				// controlo
				$t->foreignId('uploaded_by')
					->nullable()                           // ✅ opcional: permitir null
					->constrained('users')
					->nullOnDelete();                      // ✅ se o utilizador for removido, deixa null

				$t->timestamp('valid_until')->nullable();  // expiração opcional
				$t->timestamps();
				$t->softDeletes();

				// índices
				$t->index(['documentable_type', 'documentable_id']);
				$t->index(['original_name', 'title']);
				$t->index('created_at');
				$t->index('path');                         // ✅ útil para buscas/housekeeping
				$t->unique('sha256');                      // ✅ deduplicação (permite múltiplos NULLs em MySQL)
			});
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('docs');
	}
};
