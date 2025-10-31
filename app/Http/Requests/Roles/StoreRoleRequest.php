<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()->can('access.roles.create');
	}

	public function rules(): array
	{
		return [
			'name'        => ['required', 'string', 'max:100', 'unique:roles,name'],
			'status'      => ['required', 'in:active,inactive'],
			'permissions' => ['nullable', 'array'],
			'permissions.*' => ['string', 'exists:permissions,name'],
		];
	}
}
