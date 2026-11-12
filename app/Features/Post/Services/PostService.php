<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(private readonly PostRepositoryInterface $posts) {}

    /** @param array<string, mixed> $data */
    public function createForUser(User $user, array $data, ?UploadedFile $cover = null): Post
    {
        $attributes = Arr::only($data, ['title', 'category', 'excerpt', 'content']);
        $published = (bool) ($data['is_published'] ?? false);

        $attributes += [
            'user_id' => $user->id,
            'slug' => $this->uniqueSlug((string) $attributes['title']),
            'is_published' => $published,
            'published_at' => $published ? now() : null,
            'cover_image_path' => $cover?->store('covers', 'public'),
        ];

        return $this->posts->create($attributes);
    }

    /** @param array<string, mixed> $data */
    public function update(Post $post, array $data, ?UploadedFile $cover = null): Post
    {
        $attributes = Arr::only($data, ['title', 'category', 'excerpt', 'content']);
        $published = (bool) ($data['is_published'] ?? false);

        $attributes['is_published'] = $published;
        $attributes['published_at'] = $this->resolvePublishedAt($post, $published);

        if ($cover !== null) {
            $this->deleteCoverFile($post);
            $attributes['cover_image_path'] = $cover->store('covers', 'public');
        }

        return $this->posts->update($post, $attributes);
    }

    public function delete(Post $post): void
    {
        $this->deleteCoverFile($post);
        $this->posts->delete($post);
    }

    /** Toggle Draft <-> Published. */
    public function togglePublish(Post $post): Post
    {
        $published = ! $post->is_published;

        return $this->posts->update($post, [
            'is_published' => $published,
            'published_at' => $this->resolvePublishedAt($post, $published),
        ]);
    }

    public function storeCover(Post $post, UploadedFile $cover): Post
    {
        $this->deleteCoverFile($post);

        return $this->posts->update($post, [
            'cover_image_path' => $cover->store('covers', 'public'),
        ]);
    }

    public function removeCover(Post $post): Post
    {
        $this->deleteCoverFile($post);

        return $this->posts->update($post, ['cover_image_path' => null]);
    }

    /**
     * Slug unik: "judul-artikel", lalu "judul-artikel-2", "-3", dst.
     */
    public function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 120, '')) ?: 'artikel';
        $slug = $base;
        $counter = 2;

        while ($this->posts->slugExists($slug)) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * Saat baru dipublikasikan -> isi waktu sekarang; saat tetap terbit -> pertahankan;
     * saat dijadikan draf -> kosongkan.
     */
    private function resolvePublishedAt(Post $post, bool $published): mixed
    {
        if (! $published) {
            return null;
        }

        return $post->published_at ?? now();
    }

    private function deleteCoverFile(Post $post): void
    {
        if ($post->cover_image_path) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
    }
}
