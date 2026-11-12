<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test tidak membutuhkan hasil build Vite (public/build/manifest.json).
        $this->withoutVite();

        // Lokasi halaman Inertia untuk assertInertia(). Dua penulisan folder
        // didaftarkan agar lolos di Windows (tidak peka huruf) maupun Linux CI.
        $paths = [resource_path('js/Pages'), resource_path('js/pages')];

        config()->set('inertia.pages.paths', $paths);
        config()->set('inertia.testing.page_paths', $paths);
    }

    protected function member(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }
}