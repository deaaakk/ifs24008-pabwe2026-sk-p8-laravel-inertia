<?php

namespace App\Features\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterUserAction
{
    /**
     * Pendaftaran publik selalu menghasilkan peran "member" (penulis).
     *
     * @param  array{name: string, username: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => User::ROLE_MEMBER,
        ]);

        Auth::login($user);

        return $user;
    }
}
