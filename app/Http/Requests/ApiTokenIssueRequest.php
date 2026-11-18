<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Issuing an API token over the API, by account credentials (POST /api/v1/tokens).
 *
 * Input: `email`, `password`, and the token `name` (same rule as the settings page).
 */
class ApiTokenIssueRequest extends ApiTokenStoreRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            ...parent::rules(),
        ];
    }

    /**
     * Output: the account matching the credentials. Throws a 422 on `email` when they do not
     * match, with the same message for an unknown email and a wrong password.
     *
     * @throws ValidationException
     */
    public function authenticatedUser(): User
    {
        $user = User::query()->where('email', $this->string('email')->value())->first();

        if ($user === null || ! Hash::check($this->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $user;
    }
}
