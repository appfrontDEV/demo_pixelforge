<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
     * Hash checked when the email is unknown, so both failure paths cost one bcrypt check and
     * response timing does not reveal which emails have accounts.
     */
    private static ?string $dummyHash = null;

    /**
     * Output: the account matching the credentials. Throws a 422 on `email` when they do not
     * match, with the same message and timing for an unknown email and a wrong password.
     *
     * @throws ValidationException
     */
    public function authenticatedUser(): User
    {
        $user = User::query()->where('email', $this->string('email')->value())->first();
        $hash = $user === null ? (self::$dummyHash ??= Hash::make(Str::random(40))) : $user->password;

        if (! Hash::check($this->string('password')->value(), $hash) || $user === null) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $user;
    }
}
