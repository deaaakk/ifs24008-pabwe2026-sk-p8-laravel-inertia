<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedPostItem;

interface AiClientInterface
{
    /**
     * Hasilkan draf artikel berdasarkan topik.
     *
     * @return list<GeneratedPostItem>
     *
     * @throws \RuntimeException jika layanan AI tidak tersedia / respons tidak valid
     */
    public function generatePosts(string $topic, int $count): array;
}
