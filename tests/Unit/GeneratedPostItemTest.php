<?php

namespace Tests\Unit;

use App\Features\Ai\DTO\GeneratedPostItem;
use PHPUnit\Framework\TestCase;

class GeneratedPostItemTest extends TestCase
{
    public function test_from_array_normalizes_values(): void
    {
        $item = GeneratedPostItem::fromArray([
            'title' => '  Judul ',
            'category' => 'teknologi',
            'excerpt' => ' Ringkas ',
            'content' => ' Isi ',
        ]);

        $this->assertSame(
            ['title' => 'Judul', 'category' => 'Teknologi', 'excerpt' => 'Ringkas', 'content' => 'Isi'],
            $item->toArray(),
        );
    }

    public function test_from_array_uses_defaults_for_missing_keys(): void
    {
        $item = GeneratedPostItem::fromArray([]);

        $this->assertSame(
            ['title' => '', 'category' => 'Umum', 'excerpt' => '', 'content' => ''],
            $item->toArray(),
        );
    }
}
