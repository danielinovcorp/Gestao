<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViesController extends Controller
{
	public function validate(Request $request)
	{
		// TODO: integrar com o serviço oficial do VIES.
		// Por agora, apenas normaliza e devolve "não validado".
		$request->validate([
			'country_code' => 'required|string|size:2',
			'vat_number'   => 'required|string',
		]);

		return response()->json([
			'valid' => false,
			'name' => null,
			'address' => null,
		]);
	}
}
