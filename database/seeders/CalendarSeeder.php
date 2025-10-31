<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CalendarType;
use App\Models\CalendarAction;

class CalendarSeeder extends Seeder
{
	public function run(): void
	{
		foreach (['Reunião', 'Chamada', 'Visita', 'Tarefa'] as $n) {
			CalendarType::firstOrCreate(['nome' => $n], ['ativo' => true]);
		}
		foreach (['Agendar', 'Executar', 'Follow-up', 'Enviar Email'] as $n) {
			CalendarAction::firstOrCreate(['nome' => $n], ['ativo' => true]);
		}
	}
}
