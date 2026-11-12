<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /** @param array<string, mixed> $data */
    public function update(User $user, array $data, ?UploadedFile $photo = null): User
    {
        $user->fill(Arr::only($data, ['name', 'username']));

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        if ($photo !== null) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->profile_photo_path = $photo->store('profile-photos', 'public');
        }

        $user->save();

        return $user;
    }
}
