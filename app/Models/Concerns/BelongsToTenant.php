<?php

namespace App\Models\Concerns;

use Stancl\Tenancy\Database\Concerns\BelongsToTenant as StanclBelongsToTenant;

trait BelongsToTenant
{
	use StanclBelongsToTenant;

	public static function bootBelongsToTenant()
	{
		// Escopo global: só vê dados do tenant atual
		static::addGlobalScope(new \Stancl\Tenancy\Database\TenantScope);

		// Ao criar automaticamente preenche o tenant_id
		static::creating(function ($model) {
			if (tenancy()->initialized && tenant()?->id) {
				$model->tenant_id = tenant()->id;
			}
		});

		// Opcional: segurança extra (evita vazamento entre tenants)
		static::retrieved(function ($model) {
			if (tenancy()->initialized && $model->tenant_id && $model->tenant_id !== tenant()->id) {
				$model->setRawAttributes([]); // esvazia o model
			}
		});
	}
}
