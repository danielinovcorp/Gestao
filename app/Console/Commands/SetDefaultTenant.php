<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class SetDefaultTenant extends Command
{
	protected $signature = 'tenant:default';
	protected $description = 'Define o primeiro tenant como ativo na sessão';

	public function handle()
	{
		$tenant = Tenant::first();

		if (!$tenant) {
			$this->error('Nenhum tenant encontrado!');
			return 1;
		}

		session(['tenant_id' => $tenant->id]);
		$this->info("Tenant ativo: {$tenant->name} (ID: {$tenant->id})");
		$this->info("Agora abre o site e faz login — vai funcionar 100%!");

		return 0;
	}
}
