<?php

namespace App\Features\Admin\Services;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AdminUserService
{
    public function paginateUsers(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        $term = '%'.Str::lower((string) $search).'%';

        return User::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn (Builder $q) => $q->where('is_published', true),
            ])
            ->when($search, fn (Builder $q) => $q->where(function (Builder $q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$term]);
            }))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /** @return array{users: int, admins: int, posts: int, published: int} */
    public function summary(): array
    {
        return [
            'users' => User::query()->count(),
            'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'posts' => Post::query()->count(),
            'published' => Post::query()->where('is_published', true)->count(),
        ];
    }
}
