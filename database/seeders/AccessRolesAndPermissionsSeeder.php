<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessRolesAndPermissionsSeeder extends Seeder
{
	public function run(): void
	{
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

		$permissions = [
			// Menu A
			'menu_a.create',
			'menu_a.read',
			'menu_a.update',
			'menu_a.delete',
			// Menu B
			'menu_b.create',
			'menu_b.read',
			'menu_b.update',
			'menu_b.delete',

			// Gestão de Acessos (Users / Roles)
			'access.users.view',
			'access.users.create',
			'access.users.update',
			'access.users.delete',
			'access.roles.view',
			'access.roles.create',
			'access.roles.update',
			'access.roles.delete',
		];

		foreach ($permissions as $p) {
			Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
		}

		$admin   = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
		$gestor  = Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
		$user    = Role::firstOrCreate(['name' => 'utilizador', 'guard_name' => 'web']);

		$admin->syncPermissions(Permission::pluck('name')->toArray());
		$gestor->syncPermissions([
			'menu_a.read',
			'menu_a.update',
			'menu_b.read',
			'menu_b.update',
			'access.users.view',
			'access.roles.view',
		]);
		$user->syncPermissions(['menu_a.read', 'menu_b.read']);

		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
	}
}
