<?php

namespace Database\Factories;

use App\Models\Entidade;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntidadeFactory extends Factory
{
	protected $model = Entidade::class;

	public function definition(): array
	{
		$nome = $this->faker->company();
		$nif  = $this->generateValidPortugueseNif();
		$cp   = sprintf('%04d-%03d', rand(1000, 9999), rand(100, 999));

		return [
			'is_cliente'      => false,
			'is_fornecedor'   => false,

			'nif_enc'         => $nif,
			'nif_hash'        => hash('sha256', Entidade::normalizeNif($nif)),

			'nome'            => $nome,
			'morada'          => $this->faker->streetAddress(),
			'codigo_postal'   => $cp,
			'localidade'      => $this->faker->city(),
			'pais_id'         => 1, // PT (via PaisesSeeder)

			'telefone_enc'    => $this->faker->phoneNumber(),
			'telemovel_enc'   => $this->faker->phoneNumber(),
			'website'         => $this->faker->domainName(),
			'email_enc'       => $this->faker->unique()->companyEmail(),

			'consentimento_rgpd' => $this->faker->randomElement(['sim', 'nao']),
			'observacoes'        => $this->faker->optional()->sentence(),
			'estado'             => $this->faker->randomElement(['ativo', 'inativo']),
		];
	}

	private function generateValidPortugueseNif(): string
	{
		$prefixos = [1, 2, 3, 5, 6, 8, 9];
		$n = [$prefixos[array_rand($prefixos)]];
		for ($i = 1; $i < 8; $i++) $n[$i] = rand(0, 9);
		$sum = 0;
		for ($i = 0, $w = 9; $i < 8; $i++, $w--) $sum += $n[$i] * $w;
		$dv = 11 - ($sum % 11);
		if ($dv >= 10) $dv = 0;
		return implode('', $n) . $dv;
	}

	public function cliente(): static
	{
		return $this->state(fn() => ['is_cliente' => true]);
	}
	public function fornecedor(): static
	{
		return $this->state(fn() => ['is_fornecedor' => true]);
	}
	public function ambos(): static
	{
		return $this->state(fn() => ['is_cliente' => true, 'is_fornecedor' => true]);
	}
}
