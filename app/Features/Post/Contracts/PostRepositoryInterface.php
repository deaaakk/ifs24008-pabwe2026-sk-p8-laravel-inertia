<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /** Artikel terpublikasi untuk pengunjung (tanpa kolom content). */
    public function paginatePublic(?string $search, ?string $category, int $perPage = 9): LengthAwarePaginator;

    public function findPublishedBySlug(string $slug): Post;

    /** $status: 'published' | 'draft' | null (semua). */
    public function paginateForUser(User $user, ?string $search, int $perPage = 10, ?string $status = null): LengthAwarePaginator;

    /** @return array{total: int, published: int, draft: int} */
    public function statsForUser(User $user): array;

    public function paginateAll(?string $search, int $perPage = 10): LengthAwarePaginator;

    public function paginateByUserId(int $userId, ?string $search, int $perPage = 10, ?string $status = null): LengthAwarePaginator;

    /** @return Collection<int, Post> */
    public function allForUser(User $user): Collection;

    /** @return Collection<int, Post> */
    public function all(): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Post;

    /** @param array<string, mixed> $data */
    public function update(Post $post, array $data): Post;

    public function delete(Post $post): void;

    public function slugExists(string $slug): bool;
}
