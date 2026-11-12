<?php

namespace App\Features\Post\Services;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExcelService
{
    /** Format ekspor milik penulis = format impor (round-trip). */
    public const MEMBER_HEADERS = ['title', 'category', 'excerpt', 'content', 'is_published'];

    public const ADMIN_HEADERS = [
        'id', 'author', 'username', 'title', 'slug', 'category',
        'excerpt', 'content', 'status', 'published_at', 'created_at',
    ];

    private const TRUTHY = ['1', 'true', 'ya', 'yes', 'y', 'published', 'terbit'];

    public function __construct(private readonly PostService $posts) {}

    /**
     * @param  Collection<int, Post>  $posts
     */
    public function download(Collection $posts, string $filename, bool $adminFormat = false): StreamedResponse
    {
        $headers = $adminFormat ? self::ADMIN_HEADERS : self::MEMBER_HEADERS;
        $rows = $posts->map(fn (Post $post): array => $adminFormat
            ? $this->adminRow($post)
            : $this->memberRow($post))->all();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Artikel');

        foreach (array_merge([$headers], $rows) as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                // Eksplisit string: mencegah isi artikel diawali "=" dibaca sebagai formula Excel.
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($c + 1).($r + 1),
                    (string) $value,
                    DataType::TYPE_STRING,
                );
            }
        }

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * Impor artikel (selalu masuk sebagai milik $user). Baris tanpa title/content dilewati.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(User $user, UploadedFile $file): array
    {
        try {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        } catch (ReaderException) {
            throw ValidationException::withMessages([
                'file' => 'Berkas tidak dapat dibaca. Gunakan format .xlsx, .xls, atau .csv yang valid.',
            ]);
        }

        $rows = $sheet->toArray(null, false, false, false);
        $header = array_map(
            fn ($cell): string => Str::lower(trim((string) $cell)),
            (array) array_shift($rows),
        );

        if (! in_array('title', $header, true) || ! in_array('content', $header, true)) {
            throw ValidationException::withMessages([
                'file' => 'Baris pertama harus berisi header kolom: '.implode(', ', self::MEMBER_HEADERS).' (title & content wajib).',
            ]);
        }

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach ($header as $index => $name) {
                $data[$name] = trim((string) ($row[$index] ?? ''));
            }

            if (($data['title'] ?? '') === '' || ($data['content'] ?? '') === '') {
                $skipped++;

                continue;
            }

            $this->posts->createForUser($user, [
                'title' => Str::limit($data['title'], 255, ''),
                'category' => PostCategory::fromInput($data['category'] ?? null)->value,
                'excerpt' => ($data['excerpt'] ?? '') !== '' ? $data['excerpt'] : null,
                'content' => $data['content'],
                'is_published' => in_array(Str::lower($data['is_published'] ?? ''), self::TRUTHY, true),
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /** @return list<string> */
    private function memberRow(Post $post): array
    {
        return [
            $post->title,
            $post->category->value,
            (string) $post->excerpt,
            $post->content,
            $post->is_published ? '1' : '0',
        ];
    }

    /** @return list<string> */
    private function adminRow(Post $post): array
    {
        return [
            (string) $post->id,
            $post->user->name,
            $post->user->username,
            $post->title,
            $post->slug,
            $post->category->value,
            (string) $post->excerpt,
            $post->content,
            $post->is_published ? 'Published' : 'Draft',
            (string) $post->published_at?->toDateTimeString(),
            $post->created_at->toDateTimeString(),
        ];
    }
}
