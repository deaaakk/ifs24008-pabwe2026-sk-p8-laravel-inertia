<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiPostGeneratorService implements AiClientInterface
{
    public function generatePosts(string $topic, int $count): array
    {
        $apiKey = (string) config('openai.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi di file .env.');
        }

        $response = Http::withToken($apiKey)
            ->timeout((int) config('openai.timeout'))
            ->acceptJson()
            ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                'model' => config('openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $this->userPrompt($topic, $count)],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Permintaan ke OpenAI gagal (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $items = is_array($decoded) ? ($decoded['posts'] ?? null) : null;

        if (! is_array($items) || $items === []) {
            throw new RuntimeException('Format respons OpenAI tidak valid. Silakan coba lagi.');
        }

        return array_values(array_map(
            fn ($item): GeneratedPostItem => GeneratedPostItem::fromArray((array) $item),
            array_slice($items, 0, $count),
        ));
    }

    private function systemPrompt(): string
    {
        $categories = implode(', ', array_map(fn (PostCategory $c): string => $c->value, PostCategory::cases()));

        return <<<PROMPT
        Kamu adalah penulis blog profesional berbahasa Indonesia.
        Balas HANYA dengan JSON valid berbentuk:
        {"posts":[{"title":"...","category":"...","excerpt":"...","content":"..."}]}
        Aturan: "category" harus salah satu dari [{$categories}]; "excerpt" 1-2 kalimat pemikat;
        "content" berupa artikel lengkap dalam format Markdown (gunakan heading ##, paragraf, dan daftar bila perlu), minimal 3 paragraf.
        PROMPT;
    }

    private function userPrompt(string $topic, int $count): string
    {
        return "Buat {$count} artikel blog berbeda dengan topik: \"{$topic}\".";
    }
}
