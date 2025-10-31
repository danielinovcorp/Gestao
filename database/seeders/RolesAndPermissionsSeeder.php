<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
	public function run(): void
	{
		// limpa cache das permissões
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

		// --- Permissões do seu domínio (já existiam)
		$domainPerms = [
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
		];

		// --- Permissões do módulo Gestão de Acessos (novo)
		$accessPerms = [
			'access.users.view',
			'access.users.create',
			'access.users.update',
			'access.users.delete',
			'access.roles.view',
			'access.roles.create',
			'access.roles.update',
			'access.roles.delete',
		];

		// --- (Opcional) Permissões tipo “Menu A/B” com CRUD
		$menuPerms = [
			'menu_a.create',
			'menu_a.read',
			'menu_a.update',
			'menu_a.delete',
			'menu_b.create',
			'menu_b.read',
			'menu_b.update',
			'menu_b.delete',
		];

		$all = array_merge($domainPerms, $accessPerms, $menuPerms);

		foreach ($all as $p) {
			Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
		}

		// Roles (mantive admin, gestor, operador como você já usa)
		$admin  = Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
		$gestor = Role::firstOrCreate(['name' => 'gestor',   'guard_name' => 'web']);
		$oper   = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);

		// Admin: tudo
		$admin->syncPermissions(Permission::pluck('name')->toArray());

		// Gestor: leitura/edição principais + ver acessos
		$gestor->syncPermissions([
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
			// menus (se quiser)
			'menu_a.read',
			'menu_a.update',
			'menu_b.read',
			'menu_b.update',
			// gestão de acessos (ver apenas)
			'access.users.view',
			'access.roles.view',
			'ver ordens',
			'criar ordens',
			'editar ordens',
			'remover ordens',
		]);

		// Operador: leituras
		$oper->syncPermissions([
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
