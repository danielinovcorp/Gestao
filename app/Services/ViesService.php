<?php

namespace App\Services;

use SoapClient;
use SoapFault;

class ViesService
{
	private const WSDL = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

	/**
	 * Ex.: validate('PT', '509999999')
	 * Retorna:
	 * [
	 *   'valid' => bool,
	 *   'countryCode' => 'PT',
	 *   'vatNumber' => '509999999',
	 *   'name' => 'NOME OU ---',
	 *   'address' => 'ENDEREÇO OU ---'
	 * ]
	 */
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

			return [
				'valid'       => (bool)($res->valid ?? false),
				'countryCode' => (string)($res->countryCode ?? $cc),
				'vatNumber'   => (string)($res->vatNumber ?? $vat),
				'name'        => $this->cleanString($res->name ?? ''),
				'address'     => $this->cleanString($res->address ?? ''),
			];
		} catch (SoapFault $e) {
			// Quando o serviço está indisponível ou o NIF é inválido, o VIES lança faults
			return [
				'valid'       => false,
				'countryCode' => $cc,
				'vatNumber'   => $vat,
				'name'        => '',
				'address'     => '',
				'error'       => $e->getMessage(),
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
		// Endereços do VIES vêm com quebras de linha, normalizamos
		return preg_replace('/\s+/', ' ', $s);
	}
}
