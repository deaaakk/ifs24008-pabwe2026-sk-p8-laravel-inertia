<?php

namespace Tests\Unit;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Policies\PostPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_and_relations(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertCount(2, $user->posts);
        $this->assertTrue($user->posts->first()->user->is($user));
    }

    public function test_user_photo_url(): void
    {
        Storage::fake('public');

        $this->assertNull(User::factory()->create()->photo_url);

        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/a.jpg']);
        $this->assertStringEndsWith('profile-photos/a.jpg', $user->photo_url);
    }

    public function test_post_casts_cover_url_and_reading_time(): void
    {
        Storage::fake('public');

        $post = Post::factory()->create(['category' => 'Bisnis', 'content' => '']);
        $this->assertSame(PostCategory::Bisnis, $post->category);
        $this->assertTrue($post->is_published);
        $this->assertNull($post->cover_url);
        $this->assertSame(1, $post->readingTimeMinutes());

        $post->update(['cover_image_path' => 'covers/x.jpg', 'content' => str_repeat('kata ', 450)]);
        $this->assertStringEndsWith('covers/x.jpg', $post->cover_url);
        $this->assertSame(3, $post->readingTimeMinutes());
    }

    public function test_draft_factory_state(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_policy_allows_only_owner_to_manage(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $policy = new PostPolicy;

        $this->assertTrue($policy->manage($owner, $post));
        $this->assertFalse($policy->manage($other, $post));
        $this->assertFalse($policy->manage($admin, $post));
    }
}
