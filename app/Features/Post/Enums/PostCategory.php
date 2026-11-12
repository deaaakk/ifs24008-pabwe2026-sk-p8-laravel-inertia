<?php

namespace App\Features\Post\Enums;

enum PostCategory: string
{
    case Teknologi = 'Teknologi';
    case Pendidikan = 'Pendidikan';
    case GayaHidup = 'Gaya Hidup';
    case Bisnis = 'Bisnis';
    case Opini = 'Opini';
    case Umum = 'Umum';

    public function description(): string
    {
        return match ($this) {
            self::Teknologi => 'Teknologi & Koding',
            self::Pendidikan => 'Pendidikan & Tutorial',
            self::GayaHidup => 'Gaya Hidup & Produktivitas',
            self::Bisnis => 'Bisnis & Karir',
            self::Opini => 'Opini & Pemikiran',
            self::Umum => 'Umum',
        };
    }

    /**
     * Daftar kategori untuk dropdown / filter di frontend.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->description()],
            self::cases(),
        );
    }

    /**
     * Konversi input bebas (mis. dari Excel / AI) ke kategori; fallback ke Umum.
     */
    public static function fromInput(?string $input): self
    {
        $needle = mb_strtolower(trim((string) $input));

        foreach (self::cases() as $case) {
            if (mb_strtolower($case->value) === $needle) {
                return $case;
            }
        }

        return self::Umum;
    }
}
