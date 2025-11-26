<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tenants', function (Blueprint $table) {
			$table->string('id')->primary();
			$table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
			$table->string('name');
			$table->string('slug')->unique();
			$table->json('data')->nullable();
			$table->timestamp('trial_ends_at')->nullable();
			$table->string('plan')->default('free');
			$table->timestamp('plan_ends_at')->nullable();
			$table->timestamps();
		});

		Schema::create('tenant_user', function (Blueprint $table) {
			$table->id();
			$table->string('tenant_id');
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('role')->default('member');
			$table->timestamps();

			$table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
			$table->unique(['tenant_id', 'user_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tenant_user');
		Schema::dropIfExists('tenants');
	}
};
