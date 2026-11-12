<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberPostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = $this->member(['username' => 'penulis']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Judul Artikel Baru',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan singkat.',
            'content' => "## Halo\n\nIsi artikel.",
            'is_published' => true,
        ], $overrides);
    }

    // ---------- index ----------

    public function test_index_lists_only_own_posts_with_stats(): void
    {
        Post::factory()->count(2)->create(['user_id' => $this->user->id]);
        Post::factory()->draft()->create(['user_id' => $this->user->id]);
        Post::factory()->count(4)->create(); // milik orang lain

        $this->actingAs($this->user)->get('/dashboard/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Index')
                ->has('posts.data', 3)
                ->where('stats', ['total' => 3, 'published' => 2, 'draft' => 1])
                ->has('categories', 6)
                ->where('aiMax', 5)
            );
    }

    public function test_index_search_filters_own_posts(): void
    {
        Post::factory()->create(['user_id' => $this->user->id, 'title' => 'Tentang PostgreSQL']);
        Post::factory()->create(['user_id' => $this->user->id, 'title' => 'Tentang React']);

        $this->actingAs($this->user)->get('/dashboard/posts?q=postgres')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('filters.q', 'postgres'));
    }

    public function test_index_filters_by_status_from_stat_cards(): void
    {
        Post::factory()->count(2)->create(['user_id' => $this->user->id]);
        Post::factory()->draft()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        $this->get('/dashboard/posts?status=published')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2)->where('filters.status', 'published'));

        $this->get('/dashboard/posts?status=draft')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('filters.status', 'draft'));

        $this->get('/dashboard/posts?status=apa-saja')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 3)->where('filters.status', ''));
    }

    // ---------- store ----------

    public function test_store_creates_published_post_with_unique_slug(): void
    {
        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload())
            ->assertRedirect(route('posts.index'))
            ->assertSessionHas('success');

        $this->actingAs($this->user)->post('/dashboard/posts', $this->payload());

        $posts = Post::orderBy('id')->get();
        $this->assertCount(2, $posts);
        $this->assertSame('judul-artikel-baru', $posts[0]->slug);
        $this->assertSame('judul-artikel-baru-2', $posts[1]->slug);
        $this->assertTrue($posts[0]->is_published);
        $this->assertNotNull($posts[0]->published_at);
        $this->assertSame($this->user->id, $posts[0]->user_id);
    }

    public function test_store_defaults_to_draft_and_accepts_cover(): void
    {
        $data = $this->payload(['cover' => UploadedFile::fake()->image('cover.jpg', 600, 400)]);
        unset($data['is_published']);

        $this->actingAs($this->user)->post('/dashboard/posts', $data)->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        $this->assertNotNull($post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);
    }

    public function test_store_validation(): void
    {
        $this->actingAs($this->user)->post('/dashboard/posts', [
            'title' => '',
            'category' => 'Olahraga',
            'content' => '',
            'cover' => UploadedFile::fake()->create('cover.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['title', 'category', 'content', 'cover']);

        $this->assertSame(0, Post::count());
    }

    // ---------- show ----------

    public function test_owner_can_view_post_detail(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->get("/dashboard/posts/{$post->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Show')
                ->where('post.id', $post->id)
                ->has('readingTime')
                ->has('categories', 6)
            );
    }

    public function test_member_cannot_access_other_members_post(): void
    {
        $other = Post::factory()->create();
        $this->actingAs($this->user);

        $this->get("/dashboard/posts/{$other->id}")->assertForbidden();
        $this->put("/dashboard/posts/{$other->id}", $this->payload())->assertForbidden();
        $this->delete("/dashboard/posts/{$other->id}")->assertForbidden();
        $this->patch("/dashboard/posts/{$other->id}/publish")->assertForbidden();
        $this->post("/dashboard/posts/{$other->id}/cover", [])->assertForbidden();
        $this->delete("/dashboard/posts/{$other->id}/cover")->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $other->id]);
    }

    public function test_admin_cannot_modify_member_posts(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->admin())->delete("/dashboard/posts/{$post->id}")->assertForbidden();
    }

    public function test_missing_or_non_numeric_post_returns_404(): void
    {
        $this->actingAs($this->user);

        $this->get('/dashboard/posts/99999')->assertNotFound();
        $this->get('/dashboard/posts/abc')->assertNotFound();
    }

    // ---------- update ----------

    public function test_update_changes_fields_but_keeps_slug_and_published_at(): void
    {
        $publishedAt = now()->subDays(3)->startOfSecond();
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'slug-tetap',
            'published_at' => $publishedAt,
        ]);

        $this->actingAs($this->user)
            ->put("/dashboard/posts/{$post->id}", $this->payload(['title' => 'Judul Diubah', 'category' => 'Opini']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertSame('Judul Diubah', $post->title);
        $this->assertSame('Opini', $post->category->value);
        $this->assertSame('slug-tetap', $post->slug);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
    }

    public function test_update_can_unpublish_and_publish(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        $this->put("/dashboard/posts/{$post->id}", $this->payload(['is_published' => false]));
        $this->assertFalse($post->refresh()->is_published);
        $this->assertNull($post->published_at);

        $this->put("/dashboard/posts/{$post->id}", $this->payload(['is_published' => true]));
        $this->assertTrue($post->refresh()->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_update_replaces_cover_and_removes_old_file(): void
    {
        Storage::disk('public')->put('covers/lama.jpg', 'x');
        $post = Post::factory()->create(['user_id' => $this->user->id, 'cover_image_path' => 'covers/lama.jpg']);

        $this->actingAs($this->user)->put("/dashboard/posts/{$post->id}", $this->payload([
            'cover' => UploadedFile::fake()->image('baru.png'),
        ]));

        $post->refresh();
        Storage::disk('public')->assertMissing('covers/lama.jpg');
        Storage::disk('public')->assertExists($post->cover_image_path);
        $this->assertNotSame('covers/lama.jpg', $post->cover_image_path);
    }

    public function test_update_validation(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->put("/dashboard/posts/{$post->id}", $this->payload(['title' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('title');
    }

    // ---------- destroy / publish ----------

    public function test_destroy_deletes_post_and_cover(): void
    {
        Storage::disk('public')->put('covers/hapus.jpg', 'x');
        $post = Post::factory()->create(['user_id' => $this->user->id, 'cover_image_path' => 'covers/hapus.jpg']);

        $this->actingAs($this->user)->delete("/dashboard/posts/{$post->id}")
            ->assertRedirect(route('posts.index'));

        $this->assertModelMissing($post);
        Storage::disk('public')->assertMissing('covers/hapus.jpg');
    }

    public function test_destroy_works_without_cover(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->delete("/dashboard/posts/{$post->id}")->assertRedirect();

        $this->assertModelMissing($post);
    }

    public function test_toggle_publish_switches_between_draft_and_published(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        $this->patch("/dashboard/posts/{$post->id}/publish")->assertSessionHas('success', 'Artikel dijadikan draf.');
        $this->assertFalse($post->refresh()->is_published);
        $this->assertNull($post->published_at);

        $this->patch("/dashboard/posts/{$post->id}/publish")->assertSessionHas('success', 'Artikel dipublikasikan.');
        $this->assertTrue($post->refresh()->is_published);
        $this->assertNotNull($post->published_at);
    }

    // ---------- cover ----------

    public function test_upload_and_remove_cover(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => UploadedFile::fake()->image('a.jpg')])
            ->assertRedirect();
        $first = $post->refresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => UploadedFile::fake()->image('b.webp')]);
        $second = $post->refresh()->cover_image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->delete("/dashboard/posts/{$post->id}/cover")->assertRedirect();
        $this->assertNull($post->refresh()->cover_image_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_upload_cover_validation(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        $this->post("/dashboard/posts/{$post->id}/cover", [])->assertSessionHasErrors('cover');
        $this->post("/dashboard/posts/{$post->id}/cover", ['cover' => UploadedFile::fake()->create('x.pdf', 5)])
            ->assertSessionHasErrors('cover');
    }
}
