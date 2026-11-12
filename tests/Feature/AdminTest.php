<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function rows($response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        return $rows;
    }

    public function test_member_is_forbidden_from_admin_area(): void
    {
        $this->actingAs($this->member());

        foreach (['/admin/users', '/admin/posts', '/admin/posts/export', '/admin/users/1/posts', '/admin/users/1/posts/export'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_admin_lists_users_with_post_counts_and_summary(): void
    {
        $admin = $this->admin(['name' => 'Admin Utama']);
        $writer = $this->member(['name' => 'Budi Penulis', 'username' => 'budi']);
        $this->member(['name' => 'Siti Pembaca', 'username' => 'siti']);
        Post::factory()->count(2)->create(['user_id' => $writer->id]);
        Post::factory()->draft()->create(['user_id' => $writer->id]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data', 3)
                ->where('summary', ['users' => 3, 'admins' => 1, 'posts' => 3, 'published' => 2])
                ->where('users.data.1.username', 'budi')
                ->where('users.data.1.posts_count', 3)
                ->where('users.data.1.published_posts_count', 2)
            );
    }

    public function test_admin_can_search_users(): void
    {
        $admin = $this->admin();
        $this->member(['name' => 'Budi Penulis', 'username' => 'budi']);
        $this->member(['name' => 'Siti', 'username' => 'siti']);

        $this->actingAs($admin)->get('/admin/users?q=BUD')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('filters.q', 'BUD'));
    }

    public function test_admin_monitors_posts_of_a_specific_user(): void
    {
        $writer = $this->member(['username' => 'budi']);
        Post::factory()->create(['user_id' => $writer->id, 'title' => 'Tulisan Budi']);
        Post::factory()->create(['user_id' => $writer->id, 'title' => 'Lainnya']);
        Post::factory()->create(['title' => 'Bukan Budi']);

        $this->actingAs($this->admin())->get("/admin/users/{$writer->id}/posts")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserPostList')
                ->where('author.username', 'budi')
                ->has('posts.data', 2)
            );

        $this->actingAs($this->admin())->get("/admin/users/{$writer->id}/posts?q=tulisan")
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }

    public function test_admin_monitors_all_posts_and_can_search(): void
    {
        Post::factory()->count(2)->create();
        Post::factory()->draft()->create(['title' => 'Draf Rahasia Penulis']);

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/PostList')->has('posts.data', 3));

        $this->actingAs($admin)->get('/admin/posts?q=rahasia')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }

    public function test_admin_exports_all_posts(): void
    {
        $writer = $this->member(['name' => 'Budi', 'username' => 'budi']);
        Post::factory()->create(['user_id' => $writer->id, 'title' => 'Satu']);
        Post::factory()->draft()->create(['title' => 'Dua']);

        $response = $this->actingAs($this->admin())->get('/admin/posts/export');

        $response->assertOk()->assertDownload('semua-artikel.xlsx');
        $rows = $this->rows($response);

        $this->assertSame(PostExcelService::ADMIN_HEADERS, $rows[0]);
        $this->assertCount(3, $rows);
        $statuses = array_column($rows, 8);
        $this->assertContains('Published', $statuses);
        $this->assertContains('Draft', $statuses);
    }

    public function test_admin_exports_posts_of_one_user(): void
    {
        $writer = $this->member(['username' => 'budi']);
        Post::factory()->count(2)->create(['user_id' => $writer->id]);
        Post::factory()->create();

        $response = $this->actingAs($this->admin())->get("/admin/users/{$writer->id}/posts/export");

        $response->assertOk()->assertDownload('artikel-budi.xlsx');
        $rows = $this->rows($response);
        $this->assertCount(3, $rows);
        $this->assertSame('budi', $rows[1][2]);
    }
}
