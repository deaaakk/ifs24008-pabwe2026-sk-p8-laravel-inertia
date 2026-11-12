<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController extends Controller
{
    public function __construct(private readonly PostRepositoryInterface $posts) {}

    /** GET / — beranda: hero + galeri artikel. */
    public function home(Request $request): Response
    {
        return $this->gallery($request, 'home');
    }

    /** GET /articles — daftar artikel + pencarian + filter kategori. */
    public function index(Request $request): Response
    {
        return $this->gallery($request, 'index');
    }

    /** GET /articles/{slug} */
    public function show(string $slug): Response
    {
        $post = $this->posts->findPublishedBySlug($slug);

        return Inertia::render('Public/PostShow', [
            'post' => $post,
            'readingTime' => $post->readingTimeMinutes(),
        ]);
    }

    private function gallery(Request $request, string $mode): Response
    {
        $search = $request->string('q')->trim()->toString();
        $category = PostCategory::tryFrom($request->string('category')->toString())?->value;

        return Inertia::render('Public/PostIndex', [
            'mode' => $mode,
            'posts' => $this->posts->paginatePublic($search, $category)->withQueryString(),
            'filters' => ['q' => $search, 'category' => $category ?? ''],
            'categories' => PostCategory::options(),
        ]);
    }
}
