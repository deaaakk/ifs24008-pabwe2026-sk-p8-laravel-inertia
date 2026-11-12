<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_current_user(): void
    {
        $user = $this->member(['name' => 'Penulis', 'username' => 'penulis']);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('profile.username', 'penulis')
                ->missing('profile.password')
            );
    }

    public function test_profile_requires_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_update_name_and_username_keeps_password(): void
    {
        $user = $this->member(['username' => 'lama', 'password' => 'password-lama']);

        $this->actingAs($user)->put('/profile', ['name' => 'Nama Baru', 'username' => 'baru'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru', $user->username);
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_update_password_and_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/lama.jpg', 'x');
        $user = $this->member(['username' => 'penulis', 'profile_photo_path' => 'profile-photos/lama.jpg']);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'username' => 'penulis',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('password-baru', $user->password));
        Storage::disk('public')->assertMissing('profile-photos/lama.jpg');
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_first_photo_upload_when_none_exists(): void
    {
        Storage::fake('public');
        $user = $this->member(['username' => 'penulis']);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'username' => 'penulis',
            'photo' => UploadedFile::fake()->image('foto.png'),
        ]);

        Storage::disk('public')->assertExists($user->refresh()->profile_photo_path);
    }

    public function test_update_validation(): void
    {
        $this->member(['username' => 'dipakai']);
        $user = $this->member(['username' => 'saya']);

        $this->actingAs($user)->put('/profile', [
            'name' => '',
            'username' => 'dipakai',
            'password' => 'pendek',
            'password_confirmation' => 'x',
            'photo' => UploadedFile::fake()->create('a.pdf', 5),
        ])->assertSessionHasErrors(['name', 'username', 'password', 'photo']);
    }
}
