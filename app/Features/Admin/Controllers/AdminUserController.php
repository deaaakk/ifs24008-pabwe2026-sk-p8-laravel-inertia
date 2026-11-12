<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Services\PostExcelService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $service,
        private readonly PostRepositoryInterface $posts,
        private readonly PostExcelService $excel,
    ) {}

    /** GET /admin/users */
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        return Inertia::render('Admin/UserList', [
            'users' => $this->service->paginateUsers($search)->withQueryString(),
            'summary' => $this->service->summary(),
            'filters' => ['q' => $search],
        ]);
    }

    /** GET /admin/users/{user}/posts (read-only) */
    public function posts(Request $request, User $user): Response
    {
        $search = $request->string('q')->trim()->toString();

        return Inertia::render('Admin/UserPostList', [
            'author' => $user,
            'posts' => $this->posts->paginateByUserId($user->id, $search)->withQueryString(),
            'filters' => ['q' => $search],
        ]);
    }

    /** GET /admin/users/{user}/posts/export */
    public function exportPosts(User $user): StreamedResponse
    {
        return $this->excel->download(
            $this->posts->allForUser($user),
            "artikel-{$user->username}.xlsx",
            adminFormat: true,
        );
    }
}
