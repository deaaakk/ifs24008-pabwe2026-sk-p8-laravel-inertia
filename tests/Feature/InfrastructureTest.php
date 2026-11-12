<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostService;
use App\Http\Middleware\EnsureRole;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_container_bindings(): void
    {
        $this->assertInstanceOf(PostRepository::class, app(PostRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_policy_is_registered_for_post_model(): void
    {
        $owner = $this->member();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue(Gate::forUser($owner)->allows('manage', $post));
        $this->assertTrue(Gate::forUser($this->member())->denies('manage', $post));
    }

    public function test_force_https_config_is_applied_on_boot(): void
    {
        config(['app.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/artikel'));
    }

    public function test_ensure_role_allows_multiple_roles_and_blocks_others(): void
    {
        Route::middleware(['web', 'auth', 'role:admin,member'])->get('/_uji/dua-peran', fn () => 'ok');
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_uji/admin', fn () => 'ok');

        $this->actingAs($this->member())->get('/_uji/dua-peran')->assertOk();
        $this->actingAs($this->member())->get('/_uji/admin')->assertForbidden();
        $this->actingAs($this->admin())->get('/_uji/admin')->assertOk();
    }

    public function test_ensure_role_rejects_request_without_user(): void
    {
        $this->expectException(HttpException::class);

        (new EnsureRole)->handle(Request::create('/x'), fn () => response('ok'), 'admin');
    }

    public function test_slug_generation_handles_collisions_and_empty_titles(): void
    {
        $service = app(PostService::class);

        $this->assertSame('hello-world', $service->uniqueSlug('Hello World'));

        Post::factory()->create(['slug' => 'hello-world']);
        Post::factory()->create(['slug' => 'hello-world-2']);
        $this->assertSame('hello-world-3', $service->uniqueSlug('Hello World'));

        $this->assertSame('artikel', $service->uniqueSlug('!!!'));
    }

    public function test_seeder_creates_demo_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('username', 'admin')->firstOrFail()->isAdmin());
        $this->assertFalse(User::where('username', 'member')->firstOrFail()->isAdmin());
        $this->assertGreaterThan(10, Post::count());

        $this->post('/login', ['username' => 'member', 'password' => 'member'])->assertRedirect(route('posts.index'));
    }
}
