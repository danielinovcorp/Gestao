<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LogsController extends Controller
{
	public function index(Request $request)
	{
		$q        = trim((string) $request->get('q', ''));
		$userId   = $request->integer('user_id');
		$menu     = trim((string) $request->get('menu', ''));
		$acao     = trim((string) $request->get('acao', ''));
		$fromDate = $request->date('from');
		$toDate   = $request->date('to');
		$perPage  = (int) ($request->integer('per_page') ?: 15);

		$query = DB::table('activity_log as a')
			->leftJoin('users as u', 'u.id', '=', 'a.causer_id')
			->selectRaw("
                a.id,
                a.created_at,
                a.description as acao,
                a.log_name as menu,
                u.name as utilizador,
                JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.ip')) as ip,
                JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.user_agent')) as user_agent
            ")
			->when($q, function ($qr) use ($q) {
				$qr->where(function ($w) use ($q) {
					$w->where('a.description', 'like', "%{$q}%")
						->orWhere('a.log_name', 'like', "%{$q}%")
						->orWhere('u.name', 'like', "%{$q}%")
						->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(a.properties, '$.ip')) like ?", ["%{$q}%"]);
				});
			})
			->when($userId, fn($qr) => $qr->where('a.causer_id', $userId))
			->when($menu !== '', fn($qr) => $qr->where('a.log_name', 'like', "%{$menu}%"))
			->when($acao !== '', fn($qr) => $qr->where('a.description', 'like', "%{$acao}%"))
			->when($fromDate, fn($qr) => $qr->whereDate('a.created_at', '>=', $fromDate))
			->when($toDate, fn($qr) => $qr->whereDate('a.created_at', '<=', $toDate))
			->orderByDesc('a.created_at');

		$items = $query->paginate($perPage)->appends($request->query());

		$userOptions = DB::table('users')->select('id', 'name')->orderBy('name')->get();
		$menuOptions = DB::table('activity_log')->select('log_name')->distinct()->orderBy('log_name')->pluck('log_name');

		return Inertia::render('Logs/Index', [
			'items'       => $items,
			'filters'     => [
				'q' => $q,
				'user_id' => $userId,
				'menu' => $menu,
				'acao' => $acao,
				'from' => $fromDate?->format('Y-m-d'),
				'to' => $toDate?->format('Y-m-d'),
				'per_page' => $perPage,
			],
			'userOptions' => $userOptions,
			'menuOptions' => $menuOptions,
		]);
	}
}
