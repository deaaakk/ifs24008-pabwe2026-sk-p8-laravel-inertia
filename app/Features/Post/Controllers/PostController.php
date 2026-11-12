<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $service,
        private readonly PostRepositoryInterface $posts,
        private readonly PostExcelService $excel,
        private readonly AiClientInterface $ai,
    ) {}

    /** GET /dashboard/posts */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('q')->trim()->toString();
        $status = in_array($request->query('status'), ['published', 'draft'], true) ? $request->query('status') : null;

        return Inertia::render('Post/Index', [
            'posts' => $this->posts->paginateForUser($user, $search, 10, $status)->withQueryString(),
            'stats' => $this->posts->statsForUser($user),
            'filters' => ['q' => $search, 'status' => $status ?? ''],
            'categories' => PostCategory::options(),
            'aiMax' => (int) config('openai.max_posts'),
        ]);
    }

    /** POST /dashboard/posts */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $this->service->createForUser($request->user(), $data, $request->file('cover'));

        return redirect()->route('posts.index')->with('success', 'Artikel berhasil dibuat.');
    }

    /** GET /dashboard/posts/{post} */
    public function show(Post $post): Response
    {
        return Inertia::render('Post/Show', [
            'post' => $post,
            'readingTime' => $post->readingTimeMinutes(),
            'categories' => PostCategory::options(),
        ]);
    }

    /** PUT /dashboard/posts/{post} */
    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $this->service->update($post, $data, $request->file('cover'));

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    /** DELETE /dashboard/posts/{post} */
    public function destroy(Post $post): RedirectResponse
    {
        $this->service->delete($post);

        return redirect()->route('posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    /** PATCH /dashboard/posts/{post}/publish */
    public function togglePublish(Post $post): RedirectResponse
    {
        $post = $this->service->togglePublish($post);

        return back()->with('success', $post->is_published ? 'Artikel dipublikasikan.' : 'Artikel dijadikan draf.');
    }

    /** POST /dashboard/posts/{post}/cover */
    public function uploadCover(Request $request, Post $post): RedirectResponse
    {
        $request->validate(['cover' => ['required', ...$this->coverRules()]]);

        $this->service->storeCover($post, $request->file('cover'));

        return back()->with('success', 'Foto cover diperbarui.');
    }

    /** DELETE /dashboard/posts/{post}/cover */
    public function removeCover(Post $post): RedirectResponse
    {
        $this->service->removeCover($post);

        return back()->with('success', 'Foto cover dihapus.');
    }

    /** GET /dashboard/posts/export */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        return $this->excel->download($this->posts->allForUser($user), "artikel-{$user->username}.xlsx");
    }

    /** POST /dashboard/posts/import */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ]);

        $result = $this->excel->import($request->user(), $request->file('file'));

        return redirect()->route('posts.index')->with(
            'success',
            "{$result['imported']} artikel berhasil diimpor, {$result['skipped']} baris dilewati."
        );
    }

    /** POST /dashboard/posts/ai-generate */
    public function aiGenerate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:200'],
            'count' => ['required', 'integer', 'min:1', 'max:'.config('openai.max_posts')],
        ]);

        try {
            $items = $this->ai->generatePosts($data['topic'], (int) $data['count']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['topic' => $e->getMessage()]);
        }

        return back()->with('ai_drafts', array_map(
            fn (GeneratedPostItem $item): array => $item->toArray(),
            $items,
        ));
    }

    /** POST /dashboard/posts/ai-save — simpan batch hasil AI sebagai draf. */
    public function aiSave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'posts' => ['required', 'array', 'min:1', 'max:'.config('openai.max_posts')],
            'posts.*.title' => ['required', 'string', 'max:255'],
            'posts.*.category' => ['required', Rule::enum(PostCategory::class)],
            'posts.*.excerpt' => ['nullable', 'string', 'max:500'],
            'posts.*.content' => ['required', 'string'],
        ]);

        foreach ($data['posts'] as $item) {
            $this->service->createForUser($request->user(), $item + ['is_published' => false]);
        }

        return redirect()->route('posts.index')->with(
            'success',
            count($data['posts']).' draf artikel dari AI berhasil disimpan.'
        );
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'cover' => ['nullable', ...$this->coverRules()],
        ];
    }

    /** @return list<string> */
    private function coverRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }
}
