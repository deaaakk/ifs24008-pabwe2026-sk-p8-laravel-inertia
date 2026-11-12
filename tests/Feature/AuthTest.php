<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_are_available_for_guests(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Auth/Login'));
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Auth/Register'));
    }

    public function test_member_login_redirects_to_dashboard(): void
    {
        $this->member(['username' => 'penulis', 'password' => 'rahasia123']);

        $this->post('/login', ['username' => 'penulis', 'password' => 'rahasia123'])
            ->assertRedirect(route('posts.index'));

        $this->assertAuthenticated();
    }

    public function test_admin_login_redirects_to_admin_panel(): void
    {
        $this->admin(['username' => 'admin', 'password' => 'admin']);

        $this->post('/login', ['username' => 'admin', 'password' => 'admin', 'remember' => true])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_login_redirects_to_intended_url(): void
    {
        $this->member(['username' => 'penulis', 'password' => 'rahasia123']);

        $this->get('/profile')->assertRedirect('/login');

        $this->post('/login', ['username' => 'penulis', 'password' => 'rahasia123'])
            ->assertRedirect('/profile');
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $this->member(['username' => 'penulis', 'password' => 'rahasia123']);

        $this->from('/login')
            ->post('/login', ['username' => 'penulis', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['username' => 'Username atau password salah.']);

        $this->assertGuest();
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['username', 'password']);
    }

    public function test_register_creates_member_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Penulis Baru',
            'username' => 'penulis_baru',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin', // harus diabaikan
        ])->assertRedirect(route('posts.index'));

        $user = User::where('username', 'penulis_baru')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_validation(): void
    {
        $this->member(['username' => 'dipakai']);

        $this->post('/register', [
            'name' => '',
            'username' => 'dipakai',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['name', 'username', 'password']);

        $this->post('/register', [
            'name' => 'X',
            'username' => 'spasi salah',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['username']);
    }

    public function test_logout_ends_session(): void
    {
        $this->actingAs($this->member())->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_redirected_away_from_guest_pages(): void
    {
        $this->actingAs($this->member())->get('/login')->assertRedirect('/dashboard/posts');
        $this->actingAs($this->admin())->get('/register')->assertRedirect('/admin/users');
    }

    public function test_dashboard_requires_login(): void
    {
        $this->get('/dashboard/posts')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }
}
