<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    /**
     * @param  array{username: string, password: string}  $credentials
     */
    public function execute(array $credentials, bool $remember = false): bool
    {
        return Auth::attempt(Arr::only($credentials, ['username', 'password']), $remember);
    }
}
