<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocUpdateRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}
	public function rules(): array
	{
		return [
			'title' => ['nullable', 'string', 'max:191'],
			'notes' => ['nullable', 'string', 'max:2000'],
			'tags'  => ['nullable', 'array'],
			'tags.*' => ['string', 'max:32'],
			'valid_until' => ['nullable', 'date'],
		];
	}
}
