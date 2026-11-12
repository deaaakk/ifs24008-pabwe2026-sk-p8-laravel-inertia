<?php

namespace App\Features\Post\Policies;

use App\Features\Post\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Hanya pemilik artikel yang boleh mengelola (ubah/hapus/publikasi/cover).
     * Admin sengaja tidak diberi akses tulis: perannya read-only monitoring.
     */
    public function manage(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
