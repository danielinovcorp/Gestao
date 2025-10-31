<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		// 🔹 Executa o seeder de roles e permissões
		$this->call(RolesAndPermissionsSeeder::class);

		// 🔹 Cria o utilizador administrador principal
		$admin = User::firstOrCreate(
			['email' => 'admin@gestao.test'],
			[
				'name' => 'Administrador',
				'password' => bcrypt('password'), // troque depois
				'phone' => null,
				'status' => 'active',
			]
		);

		// 🔹 Atribui o papel admin (se existir)
		if (Role::where('name', 'admin')->exists()) {
			$admin->assignRole('admin');
		}

		$this->call([
			AccessRolesAndPermissionsSeeder::class,
		]);

		// 🔹 (Opcional) cria alguns utilizadores extra de teste
		/*
        User::factory(5)->create()->each(function ($user) {
            $user->assignRole('operador');
        });
        */
	}
}
