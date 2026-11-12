<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PostRepository implements PostRepositoryInterface
{
    private const AUTHOR_COLUMNS = 'user:id,name,username,profile_photo_path';

    /** Kolom ringan untuk daftar publik (tanpa content). */
    private const LIST_COLUMNS = [
        'id', 'user_id', 'title', 'slug', 'category', 'excerpt',
        'cover_image_path', 'is_published', 'published_at', 'created_at',
    ];

    public function paginatePublic(?string $search, ?string $category, int $perPage = 9): LengthAwarePaginator
    {
        return Post::query()
            ->select(self::LIST_COLUMNS)
            ->with(self::AUTHOR_COLUMNS)
            ->where('is_published', true)
            ->when($category, fn (Builder $q) => $q->where('category', $category))
            ->tap(fn (Builder $q) => $this->applySearch($q, $search))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): Post
    {
        return Post::query()
            ->with(self::AUTHOR_COLUMNS)
            ->where('is_published', true)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function paginateForUser(User $user, ?string $search, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        return $this->paginateByUserId($user->id, $search, $perPage, $status);
    }

    public function statsForUser(User $user): array
    {
        $total = Post::query()->where('user_id', $user->id)->count();
        $published = Post::query()->where('user_id', $user->id)->where('is_published', true)->count();

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $total - $published,
        ];
    }

    public function paginateAll(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()
            ->with(self::AUTHOR_COLUMNS)
            ->tap(fn (Builder $q) => $this->applySearch($q, $search))
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paginateByUserId(int $userId, ?string $search, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        return Post::query()
            ->with(self::AUTHOR_COLUMNS)
            ->where('user_id', $userId)
            ->when($status === 'published', fn (Builder $q) => $q->where('is_published', true))
            ->when($status === 'draft', fn (Builder $q) => $q->where('is_published', false))
            ->tap(fn (Builder $q) => $this->applySearch($q, $search))
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return Post::query()->with(self::AUTHOR_COLUMNS)->where('user_id', $user->id)->latest()->orderByDesc('id')->get();
    }

    public function all(): Collection
    {
        return Post::query()->with(self::AUTHOR_COLUMNS)->latest()->orderByDesc('id')->get();
    }

    public function create(array $data): Post
    {
        return Post::query()->create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function slugExists(string $slug): bool
    {
        return Post::query()->where('slug', $slug)->exists();
    }

    /**
     * Pencarian case-insensitive yang portabel (PostgreSQL & SQLite untuk test):
     * memakai LOWER(...) LIKE alih-alih ILIKE yang khusus PostgreSQL.
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $term = '%'.Str::lower($search).'%';

        $query->where(function (Builder $q) use ($term): void {
            $q->whereRaw('LOWER(title) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(excerpt, \'\')) LIKE ?', [$term])
                ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
        });
    }
}
