<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_public_and_lists_only_published_posts(): void
    {
        Post::factory()->count(2)->create();
        Post::factory()->draft()->create();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->where('mode', 'home')
                ->has('posts.data', 2)
                ->has('categories', 6)
                ->where('filters.q', '')
                ->where('filters.category', '')
            );
    }

    public function test_articles_index_supports_search_by_title_excerpt_and_content(): void
    {
        Post::factory()->create(['title' => 'Belajar Laravel Inertia', 'excerpt' => 'x', 'content' => 'y']);
        Post::factory()->create(['title' => 'Resep Nasi', 'excerpt' => 'Ringkasan tentang Laravel', 'content' => 'y']);
        Post::factory()->create(['title' => 'Kopi Pagi', 'excerpt' => null, 'content' => 'Tulisan memakai LARAVEL juga']);
        Post::factory()->create(['title' => 'Tidak Relevan', 'excerpt' => null, 'content' => 'z']);

        $this->get('/articles?q=laravel')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->where('mode', 'index')
                ->where('filters.q', 'laravel')
                ->has('posts.data', 3)
            );
    }

    public function test_articles_index_filters_by_category_and_ignores_invalid_category(): void
    {
        Post::factory()->create(['category' => 'Bisnis']);
        Post::factory()->count(2)->create(['category' => 'Opini']);

        $this->get('/articles?category=Opini')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2)->where('filters.category', 'Opini'));

        $this->get('/articles?category=Tidak-Ada')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 3)->where('filters.category', ''));
    }

    public function test_public_list_does_not_expose_content_column(): void
    {
        Post::factory()->create(['content' => 'RAHASIA-ISI']);

        $this->get('/articles')->assertInertia(fn (Assert $page) => $page
            ->has('posts.data.0', fn (Assert $post) => $post
                ->missing('content')
                ->has('user')
                ->etc()
            )
        );
    }

    public function test_guest_can_read_a_published_article(): void
    {
        $post = Post::factory()->create(['title' => 'Artikel Terbuka', 'slug' => 'artikel-terbuka']);

        $this->get('/articles/artikel-terbuka')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostShow')
                ->where('post.id', $post->id)
                ->where('post.title', 'Artikel Terbuka')
                ->has('post.content')
                ->has('post.user')
                ->where('readingTime', 1)
            );
    }

    public function test_draft_or_unknown_article_returns_404(): void
    {
        Post::factory()->draft()->create(['slug' => 'masih-draf']);

        $this->get('/articles/masih-draf')->assertNotFound();
        $this->get('/articles/tidak-ada')->assertNotFound();
    }

    public function test_shared_props_for_guest_and_authenticated_user(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', null)
            ->where('app.name', config('app.name'))
            ->where('flash.success', null)
            ->where('flash.ai_drafts', null)
        );

        $user = $this->member(['username' => 'penulis', 'name' => 'Penulis']);

        $this->actingAs($user)
            ->withSession(['success' => 'Halo'])
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.username', 'penulis')
                ->where('auth.user.role', 'member')
                ->where('flash.success', 'Halo')
            );
    }
}
