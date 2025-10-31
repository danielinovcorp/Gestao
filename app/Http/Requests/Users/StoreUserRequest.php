<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()->can('access.users.create');
	}

	public function rules(): array
	{
		return [
			'name'     => ['required', 'string', 'max:255'],
			'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
			'phone'    => ['nullable', 'string', 'max:30'],
			'role'     => ['required', 'string', 'exists:roles,name'],
			'status'   => ['required', 'in:active,inactive'],
			'password' => ['required', 'string', 'min:8'],
		];
	}
}
