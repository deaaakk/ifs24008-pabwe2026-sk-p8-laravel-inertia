<?php

namespace Database\Factories;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'category' => fake()->randomElement(PostCategory::cases())->value,
            'excerpt' => fake()->sentence(14),
            'content' => '## '.fake()->sentence(4)."\n\n".implode("\n\n", fake()->paragraphs(4)),
            'cover_image_path' => null,
            'is_published' => true,
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false, 'published_at' => null]);
    }
}
