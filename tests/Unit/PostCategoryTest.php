<?php

namespace Tests\Unit;

use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostCategoryTest extends TestCase
{
    public function test_has_six_categories_with_descriptions(): void
    {
        $this->assertCount(6, PostCategory::cases());

        $options = PostCategory::options();
        $this->assertCount(6, $options);
        $this->assertSame(['value' => 'Teknologi', 'label' => 'Teknologi & Koding'], $options[0]);

        $this->assertSame('Pendidikan & Tutorial', PostCategory::Pendidikan->description());
        $this->assertSame('Gaya Hidup & Produktivitas', PostCategory::GayaHidup->description());
        $this->assertSame('Bisnis & Karir', PostCategory::Bisnis->description());
        $this->assertSame('Opini & Pemikiran', PostCategory::Opini->description());
        $this->assertSame('Umum', PostCategory::Umum->description());
    }

    #[DataProvider('inputs')]
    public function test_from_input(?string $input, PostCategory $expected): void
    {
        $this->assertSame($expected, PostCategory::fromInput($input));
    }

    public static function inputs(): array
    {
        return [
            'exact' => ['Bisnis', PostCategory::Bisnis],
            'lowercase' => ['gaya hidup', PostCategory::GayaHidup],
            'padded' => ['  OPINI ', PostCategory::Opini],
            'unknown' => ['Olahraga', PostCategory::Umum],
            'null' => [null, PostCategory::Umum],
        ];
    }
}
