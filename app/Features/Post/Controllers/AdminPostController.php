<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Services\PostExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPostController extends Controller
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
        private readonly PostExcelService $excel,
    ) {}

    /** GET /admin/posts — monitoring seluruh artikel (read-only). */
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        return Inertia::render('Admin/PostList', [
            'posts' => $this->posts->paginateAll($search)->withQueryString(),
            'filters' => ['q' => $search],
        ]);
    }

    /** GET /admin/posts/export */
    public function export(): StreamedResponse
    {
        return $this->excel->download($this->posts->all(), 'semua-artikel.xlsx', adminFormat: true);
    }
}
