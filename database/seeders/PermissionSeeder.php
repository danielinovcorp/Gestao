<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
	public function run(): void
	{
		// Reset cached roles and permissions
		app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

		// Definir menus e ações
		$menus = ['users', 'roles']; // adicione mais menus conforme necessário
		$actions = ['create', 'read', 'update', 'delete'];

		// Criar permissões
		foreach ($menus as $menu) {
			foreach ($actions as $action) {
				Permission::firstOrCreate([
					'name' => "{$menu}.{$action}",
					'guard_name' => 'web',
				]);
			}
		}

		// Criar grupo admin com todas as permissões
		$admin = Role::firstOrCreate([
			'name' => 'admin',
			'guard_name' => 'web',
		]);
		$admin->givePermissionTo(Permission::all());

		// Criar grupo básico (ex: só leitura)
		$viewer = Role::firstOrCreate([
			'name' => 'viewer',
			'guard_name' => 'web',
		]);
		$viewer->givePermissionTo([
			'users.read',
			'roles.read',
		]);
	}
}
