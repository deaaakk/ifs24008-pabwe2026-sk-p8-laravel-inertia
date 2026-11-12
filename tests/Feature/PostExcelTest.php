<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PostExcelTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array<int, mixed>> */
    private function sheetRows($response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        return $rows;
    }

    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        return UploadedFile::fake()->createWithContent('import.xlsx', $content);
    }

    // ---------- export ----------

    public function test_member_exports_only_own_posts_in_import_format(): void
    {
        $user = $this->member(['username' => 'penulis']);
        Post::factory()->create(['user_id' => $user->id, 'title' => 'Milik Saya', 'is_published' => true, 'excerpt' => null]);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Draf Saya', 'content' => '=1+1']);
        Post::factory()->create(['title' => 'Milik Orang Lain']);

        $response = $this->actingAs($user)->get('/dashboard/posts/export');

        $response->assertOk()->assertDownload('artikel-penulis.xlsx');
        $rows = $this->sheetRows($response);

        $this->assertSame(PostExcelService::MEMBER_HEADERS, $rows[0]);
        $this->assertCount(3, $rows); // header + 2 artikel
        $titles = array_column($rows, 0);
        $this->assertContains('Milik Saya', $titles);
        $this->assertNotContains('Milik Orang Lain', $titles);

        // Konten berawalan "=" tetap berupa teks, bukan formula.
        $draft = $rows[array_search('Draf Saya', $titles, true)];
        $this->assertSame('=1+1', $draft[3]);
        $this->assertSame('0', $draft[4]);
    }

    // ---------- import ----------

    public function test_import_xlsx_creates_posts_for_current_user(): void
    {
        $user = $this->member();

        $file = $this->xlsx([
            PostExcelService::MEMBER_HEADERS,
            ['Artikel Satu', 'gaya hidup', 'Ringkasan', 'Isi satu', 'ya'],
            ['Artikel Dua', 'Kategori Aneh', '', 'Isi dua', '0'],
            ['', 'Bisnis', '', 'Tanpa judul', '1'],
            ['Tanpa Isi', 'Bisnis', '', '', '1'],
        ]);

        $this->actingAs($user)->post('/dashboard/posts/import', ['file' => $file])
            ->assertRedirect(route('posts.index'))
            ->assertSessionHas('success', '2 artikel berhasil diimpor, 2 baris dilewati.');

        $posts = Post::where('user_id', $user->id)->orderBy('id')->get();
        $this->assertCount(2, $posts);

        $this->assertSame('Gaya Hidup', $posts[0]->category->value);
        $this->assertTrue($posts[0]->is_published);
        $this->assertNotNull($posts[0]->published_at);
        $this->assertSame('Ringkasan', $posts[0]->excerpt);

        $this->assertSame('Umum', $posts[1]->category->value);
        $this->assertFalse($posts[1]->is_published);
        $this->assertNull($posts[1]->excerpt);
    }

    public function test_import_csv_with_flexible_header_order_and_case(): void
    {
        $user = $this->member();
        $csv = "CONTENT,Title\nIsi csv,Judul CSV\n";

        $this->actingAs($user)->post('/dashboard/posts/import', [
            'file' => UploadedFile::fake()->createWithContent('import.csv', $csv),
        ])->assertSessionHas('success', '1 artikel berhasil diimpor, 0 baris dilewati.');

        $post = Post::firstOrFail();
        $this->assertSame('Judul CSV', $post->title);
        $this->assertSame('Isi csv', $post->content);
        $this->assertSame('Umum', $post->category->value);
        $this->assertFalse($post->is_published);
    }

    public function test_import_rejects_file_without_required_headers(): void
    {
        $file = $this->xlsx([['judul', 'isi'], ['A', 'B']]);

        $this->actingAs($this->member())->post('/dashboard/posts/import', ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Post::count());
    }

    public function test_import_rejects_unreadable_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('rusak.xlsx', "PK\x03\x04\x00\x01\x02garbage\x00\xff\xfe");

        $this->actingAs($this->member())->post('/dashboard/posts/import', ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_import_validates_upload(): void
    {
        $this->actingAs($this->member());

        $this->post('/dashboard/posts/import', [])->assertSessionHasErrors('file');
        $this->post('/dashboard/posts/import', [
            'file' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('file');
    }

    public function test_import_requires_login(): void
    {
        $this->post('/dashboard/posts/import', [])->assertRedirect('/login');
    }

    public function test_exported_file_can_be_imported_back(): void
    {
        $user = $this->member(['username' => 'bolakbalik']);
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $export = $this->actingAs($user)->get('/dashboard/posts/export');
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $export->streamedContent());
        $upload = UploadedFile::fake()->createWithContent('roundtrip.xlsx', file_get_contents($path));
        @unlink($path);

        $this->post('/dashboard/posts/import', ['file' => $upload])
            ->assertSessionHas('success', '3 artikel berhasil diimpor, 0 baris dilewati.');

        $this->assertSame(6, Post::where('user_id', $user->id)->count());
        $this->assertSame(6, Post::distinct('slug')->count('slug'));
    }
}
