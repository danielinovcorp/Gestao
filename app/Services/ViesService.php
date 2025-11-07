<?php

namespace App\Services;

use SoapClient;
use SoapFault;
use Illuminate\Support\Facades\Log;

class ViesService
{
	private const WSDL = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

	public function validate(string $countryCode, string $vatNumber): array
	{
		[$cc, $vat] = $this->normalize($countryCode, $vatNumber);

		try {
			$client = new SoapClient(self::WSDL, [
				'connection_timeout' => 6,
				'exceptions' => true,
				'trace' => false,
				'cache_wsdl' => WSDL_CACHE_BOTH,
			]);

			$res = $client->checkVat([
				'countryCode' => $cc,
				'vatNumber'   => $vat,
			]);

			Log::info("VIES Validation Success: {$cc}{$vat}", [
				'valid' => $res->valid,
				'name' => $this->cleanString($res->name ?? ''),
				'address' => $this->cleanString($res->address ?? '')
			]);

			return [
				'valid'       => (bool)($res->valid ?? false),
				'countryCode' => (string)($res->countryCode ?? $cc),
				'vatNumber'   => (string)($res->vatNumber ?? $vat),
				'name'        => $this->cleanString($res->name ?? ''),
				'address'     => $this->cleanString($res->address ?? ''),
			];
		} catch (SoapFault $e) {
			Log::error("VIES Validation Error: {$cc}{$vat} - " . $e->getMessage());

			return [
				'valid'       => false,
				'countryCode' => $cc,
				'vatNumber'   => $vat,
				'name'        => '',
				'address'     => '',
				'error'       => $this->translateViesError($e->getMessage()),
			];
		}
	}

	/**
	 * Helper: aceita 'PT', 'pt', 'PT123...', '123...'
	 */
	public function validateFromRaw(string $raw): array
	{
		$raw = strtoupper(preg_replace('/\s+/', '', $raw));
		if (preg_match('/^[A-Z]{2}\d+$/', $raw)) {
			$cc  = substr($raw, 0, 2);
			$vat = substr($raw, 2);
			return $this->validate($cc, $vat);
		}
		// default Portugal se só vier números
		return $this->validate('PT', preg_replace('/\D+/', '', $raw));
	}

	/**
	 * Validação local do NIF português
	 */
	public function validatePortugueseNif(string $nif): bool
	{
		$nif = preg_replace('/[^0-9]/', '', $nif);

		if (!preg_match('/^[0-9]{9}$/', $nif)) {
			return false;
		}

		// Algoritmo de validação do NIF português
		$sum = 0;
		for ($i = 0; $i < 8; $i++) {
			$sum += $nif[$i] * (9 - $i);
		}

		$checkDigit = 11 - ($sum % 11);
		if ($checkDigit >= 10) {
			$checkDigit = 0;
		}

		return $checkDigit == $nif[8];
	}

	/**
	 * Lista de países suportados pelo VIES
	 */
	public function getEuropeanCountries(): array
	{
		return [
			'AT' => 'Áustria',
			'BE' => 'Bélgica',
			'BG' => 'Bulgária',
			'CY' => 'Chipre',
			'CZ' => 'República Checa',
			'DE' => 'Alemanha',
			'DK' => 'Dinamarca',
			'EE' => 'Estónia',
			'EL' => 'Grécia',
			'ES' => 'Espanha',
			'FI' => 'Finlândia',
			'FR' => 'França',
			'GB' => 'Reino Unido',
			'HR' => 'Croácia',
			'HU' => 'Hungria',
			'IE' => 'Irlanda',
			'IT' => 'Itália',
			'LT' => 'Lituânia',
			'LU' => 'Luxemburgo',
			'LV' => 'Letónia',
			'MT' => 'Malta',
			'NL' => 'Países Baixos',
			'PL' => 'Polónia',
			'PT' => 'Portugal',
			'RO' => 'Roménia',
			'SE' => 'Suécia',
			'SI' => 'Eslovénia',
			'SK' => 'Eslováquia',
		];
	}

	private function normalize(string $countryCode, string $vatNumber): array
	{
		$cc  = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $countryCode) ?: 'PT', 0, 2));
		$vat = preg_replace('/\D+/', '', $vatNumber);
		return [$cc, $vat];
	}

	private function cleanString(string $s): string
	{
		$s = trim($s);
		if ($s === '---') return '';
		return preg_replace('/\s+/', ' ', $s);
	}

	private function translateViesError(string $error): string
	{
		$translations = [
			'INVALID_INPUT' => 'NIF ou país inválido',
			'SERVICE_UNAVAILABLE' => 'Serviço VIES indisponível',
			'MS_UNAVAILABLE' => 'Serviço do país indisponível',
			'TIMEOUT' => 'Tempo de resposta excedido',
			'SERVER_BUSY' => 'Servidor ocupado',
		];

		foreach ($translations as $key => $translation) {
			if (stripos($error, $key) !== false) {
				return $translation;
			}
		}

		return 'Erro na validação VIES';
	}
}
