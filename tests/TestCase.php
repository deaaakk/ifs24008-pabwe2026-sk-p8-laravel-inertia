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
