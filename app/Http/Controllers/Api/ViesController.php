<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ViesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ViesController extends Controller
{
	protected $viesService;

	public function __construct(ViesService $viesService)
	{
		$this->viesService = $viesService;
	}

	public function validateVat(Request $request)
	{
		$request->validate([
			'country_code' => 'required|string|size:2',
			'vat_number'   => 'required|string|max:20',
		]);

		Log::info('VIES API Request', $request->all());

		$result = $this->viesService->validate(
			$request->country_code,
			$request->vat_number
		);

		return response()->json($result);
	}

	public function validateNif(Request $request)
	{
		$request->validate([
			'nif' => 'required|string|max:20'
		]);

		$isValid = $this->viesService->validatePortugueseNif($request->nif);

		return response()->json([
			'valid' => $isValid,
			'type' => 'portuguese_nif',
			'nif' => $request->nif
		]);
	}

	public function validateRaw(Request $request)
	{
		$request->validate([
			'vat_raw' => 'required|string|max:25'
		]);

		$result = $this->viesService->validateFromRaw($request->vat_raw);

		return response()->json($result);
	}

	public function getCountries()
	{
		$countries = $this->viesService->getEuropeanCountries();

		return response()->json($countries);
	}
}
