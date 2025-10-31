<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()->can('access.roles.update');
	}

	public function rules(): array
	{
		return [
			'name'        => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->route('role'))],
			'status'      => ['required', 'in:active,inactive'],
			'permissions' => ['nullable', 'array'],
			'permissions.*' => ['string', 'exists:permissions,name'],
		];
	}
}
