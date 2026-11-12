<?php

namespace Tests\Unit;

use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiPostGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'openai.api_key' => 'sk-test',
            'openai.base_url' => 'https://api.openai.com/v1/',
            'openai.model' => 'gpt-test',
        ]);
    }

    private function fakeCompletion(array $payload): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($payload)]]],
            ]),
        ]);
    }

    public function test_generates_posts_from_openai_response(): void
    {
        $this->fakeCompletion(['posts' => [
            ['title' => 'A', 'category' => 'Opini', 'excerpt' => 'ea', 'content' => 'ca'],
            ['title' => 'B', 'category' => 'xx', 'excerpt' => 'eb', 'content' => 'cb'],
        ]]);

        $items = (new OpenAiPostGeneratorService)->generatePosts('Laravel', 2);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedPostItem::class, $items);
        $this->assertSame('Opini', $items[0]->category);
        $this->assertSame('Umum', $items[1]->category);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-test')
                && $request['model'] === 'gpt-test'
                && str_contains($request['messages'][1]['content'], 'Laravel');
        });
    }

    public function test_result_is_limited_to_requested_count(): void
    {
        $this->fakeCompletion(['posts' => [
            ['title' => 'A', 'content' => 'x'],
            ['title' => 'B', 'content' => 'y'],
            ['title' => 'C', 'content' => 'z'],
        ]]);

        $this->assertCount(1, (new OpenAiPostGeneratorService)->generatePosts('Topik', 1));
    }

    public function test_throws_when_api_key_is_missing(): void
    {
        config(['openai.api_key' => '']);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        (new OpenAiPostGeneratorService)->generatePosts('Topik', 1);
    }

    public function test_throws_when_http_request_fails(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'x'], 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiPostGeneratorService)->generatePosts('Topik', 1);
    }

    public function test_throws_when_content_is_not_valid_json(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'bukan json']]]]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak valid');

        (new OpenAiPostGeneratorService)->generatePosts('Topik', 1);
    }

    public function test_throws_when_posts_key_is_missing_or_empty(): void
    {
        $this->fakeCompletion(['posts' => []]);

        $this->expectException(RuntimeException::class);

        (new OpenAiPostGeneratorService)->generatePosts('Topik', 1);
    }
}
