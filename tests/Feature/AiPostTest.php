<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AiPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_generate_flashes_drafts(): void
    {
        $this->mock(AiClientInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generatePosts')
                ->once()
                ->with('Laravel 13', 2)
                ->andReturn([
                    new GeneratedPostItem('Judul 1', 'Teknologi', 'Ringkas 1', 'Isi 1'),
                    new GeneratedPostItem('Judul 2', 'Opini', 'Ringkas 2', 'Isi 2'),
                ]);
        });

        $this->actingAs($this->member())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'Laravel 13', 'count' => 2])
            ->assertRedirect()
            ->assertSessionHas('ai_drafts', [
                ['title' => 'Judul 1', 'category' => 'Teknologi', 'excerpt' => 'Ringkas 1', 'content' => 'Isi 1'],
                ['title' => 'Judul 2', 'category' => 'Opini', 'excerpt' => 'Ringkas 2', 'content' => 'Isi 2'],
            ]);

        $this->assertSame(0, Post::count()); // belum disimpan
    }

    public function test_ai_generate_reports_service_errors(): void
    {
        $this->mock(AiClientInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generatePosts')->once()->andThrow(new RuntimeException('Layanan AI sibuk.'));
        });

        $this->actingAs($this->member())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'X', 'count' => 1])
            ->assertSessionHasErrors(['topic' => 'Layanan AI sibuk.']);
    }

    public function test_ai_generate_validates_input_limits(): void
    {
        $this->mock(AiClientInterface::class, fn (MockInterface $m) => $m->shouldNotReceive('generatePosts'));
        $this->actingAs($this->member());

        $this->post('/dashboard/posts/ai-generate', [])->assertSessionHasErrors(['topic', 'count']);
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'X', 'count' => 0])->assertSessionHasErrors('count');
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'X', 'count' => 6])->assertSessionHasErrors('count');
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'X', 'count' => 'abc'])->assertSessionHasErrors('count');
    }

    public function test_ai_save_stores_batch_as_drafts(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post('/dashboard/posts/ai-save', ['posts' => [
            ['title' => 'AI Satu', 'category' => 'Bisnis', 'excerpt' => 'e1', 'content' => 'c1'],
            ['title' => 'AI Dua', 'category' => 'Umum', 'excerpt' => null, 'content' => 'c2'],
        ]])->assertRedirect(route('posts.index'))
            ->assertSessionHas('success', '2 draf artikel dari AI berhasil disimpan.');

        $posts = Post::where('user_id', $user->id)->orderBy('id')->get();
        $this->assertCount(2, $posts);
        $this->assertFalse($posts[0]->is_published);
        $this->assertNull($posts[0]->published_at);
        $this->assertSame('ai-satu', $posts[0]->slug);
        $this->assertNull($posts[1]->excerpt);
    }

    public function test_ai_save_validation(): void
    {
        $this->actingAs($this->member());

        $this->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('posts');
        $this->post('/dashboard/posts/ai-save', ['posts' => [
            ['title' => '', 'category' => 'Salah', 'content' => ''],
        ]])->assertSessionHasErrors(['posts.0.title', 'posts.0.category', 'posts.0.content']);

        $this->assertSame(0, Post::count());
    }
}
