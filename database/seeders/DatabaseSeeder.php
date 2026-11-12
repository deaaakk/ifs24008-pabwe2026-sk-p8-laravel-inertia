<?php

namespace Database\Seeders;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun demo: admin / admin  ·  member / member
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $member = User::factory()->create([
            'name' => 'Penulis Contoh',
            'username' => 'member',
            'password' => 'member',
        ]);

        Post::factory()->count(8)->create(['user_id' => $member->id]);
        Post::factory()->count(3)->draft()->create(['user_id' => $member->id]);
        Post::factory()->count(3)->create(['user_id' => $admin->id]);

        User::factory()->count(3)->create()->each(
            fn (User $user) => Post::factory()->count(2)->create(['user_id' => $user->id])
        );
    }
}
