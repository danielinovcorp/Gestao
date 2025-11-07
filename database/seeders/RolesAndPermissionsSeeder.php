<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
	public function run(): void
	{
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

		// === DOMÍNIO (em português - já existente no sistema) ===
		$domain = [
			'ver entidades',
			'criar entidades',
			'editar entidades',
			'remover entidades',
			'ver contactos',
			'criar contactos',
			'editar contactos',
			'remover contactos',
			'ver artigos',
			'criar artigos',
			'editar artigos',
			'remover artigos',
			'ver documentos',
			'criar documentos',
			'editar documentos',
			'remover documentos',
			'download documentos',
			'ver ordens',
			'criar ordens',
			'editar ordens',
			'remover ordens',
		];

		// === MENUS (RESTful - em inglês) ===
		$menus = [
			'menu_a.create',
			'menu_a.read',
			'menu_a.update',
			'menu_a.delete',
			'menu_b.create',
			'menu_b.read',
			'menu_b.update',
			'menu_b.delete',
		];

		// === GESTÃO DE ACESSOS ===
		$access = [
			'access.users.view',
			'access.users.create',
			'access.users.update',
			'access.users.delete',
			'access.roles.view',
			'access.roles.create',
			'access.roles.update',
			'access.roles.delete',
		];

		$all = array_merge($domain, $menus, $access);

		foreach ($all as $p) {
			Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
		}

		// === ROLES ===
		$admin   = Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
		$gestor  = Role::firstOrCreate(['name' => 'gestor',   'guard_name' => 'web']);
		$operador = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);

		// Admin: tudo
		$admin->syncPermissions(Permission::pluck('name')->toArray());

		// Gestor: edição + visualização de acessos
		$gestor->syncPermissions([
			// Domínio
			'ver entidades',
			'criar entidades',
			'editar entidades',
			'ver contactos',
			'criar contactos',
			'editar contactos',
			'ver artigos',
			'criar artigos',
			'editar artigos',
			'ver documentos',
			'criar documentos',
			'editar documentos',
			'ver ordens',
			'criar ordens',
			'editar ordens',
			'remover ordens',
			// Menus
			'menu_a.read',
			'menu_a.update',
			'menu_b.read',
			'menu_b.update',
			// Acesso
			'access.users.view',
			'access.roles.view',
		]);

		// Operador: só leitura
		$operador->syncPermissions([
			'ver entidades',
			'ver contactos',
			'ver artigos',
			'ver documentos',
			'download documentos',
			'menu_a.read',
			'menu_b.read',
		]);

		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
	}
}
