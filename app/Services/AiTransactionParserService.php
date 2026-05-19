<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiTransactionParserService
{
    /**
     * @return array{description:string|null, amount:int|null, category:Category|null, type:string|null, response:string|null, provider:string}
     */
    public function parse(string $message): array
    {
        foreach (['groq', 'gemini'] as $provider) {
            $parsed = $this->parseWithProvider($provider, $message);

            if ($parsed !== null) {
                return $parsed + ['provider' => $provider];
            }
        }

        return [
            'description' => null,
            'amount' => null,
            'category' => null,
            'type' => null,
            'response' => null,
            'provider' => 'local',
        ];
    }

    private function parseWithProvider(string $provider, string $message): ?array
    {
        $key = config("services.{$provider}.key");

        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        try {
            $response = $provider === 'groq'
                ? $this->requestGroq($key, $message)
                : $this->requestGemini($key, $message);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || $this->isLimited($response)) {
            return null;
        }

        $content = $provider === 'groq'
            ? Arr::get($response->json(), 'choices.0.message.content')
            : Arr::get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($content)) {
            return null;
        }

        return $this->normalize($content);
    }

    private function requestGroq(string $key, string $message): Response
    {
        return Http::timeout(10)
            ->acceptJson()
            ->withToken($key)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model'),
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $message],
                ],
            ]);
    }

    private function requestGemini(string $key, string $message): Response
    {
        return Http::timeout(10)
            ->acceptJson()
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/'.config('services.gemini.model').':generateContent?key='.urlencode($key),
                [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $this->systemPrompt()],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'response_mime_type' => 'application/json',
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $message],
                            ],
                        ],
                    ],
                ],
            );
    }

    private function normalize(string $content): ?array
    {
        $payload = json_decode($this->extractJson($content), true);

        if (! is_array($payload)) {
            return null;
        }

        $amount = Arr::get($payload, 'amount');
        $type = Arr::get($payload, 'type');

        if (! is_numeric($amount) || (int) $amount <= 0 || ! in_array($type, [Transaction::TYPE_EXPENSE, Transaction::TYPE_INCOME], true)) {
            return [
                'description' => null,
                'amount' => null,
                'category' => null,
                'type' => null,
                'response' => $this->nullableString(Arr::get($payload, 'response')),
            ];
        }

        return [
            'description' => $this->nullableString(Arr::get($payload, 'description')),
            'amount' => (int) $amount,
            'category' => $this->resolveCategory($this->nullableString(Arr::get($payload, 'category')), $type),
            'type' => $type,
            'response' => $this->nullableString(Arr::get($payload, 'response')),
        ];
    }

    private function resolveCategory(?string $name, string $type): Category
    {
        $normalized = Str::upper(trim((string) preg_replace('/[^\pL\s]/u', '', $name ?? '')));

        $category = Category::query()
            ->whereIn('type', [$type, Category::TYPE_BOTH])
            ->get()
            ->first(fn (Category $category) => str_contains($normalized, Str::upper($category->name)));

        if ($category) {
            return $category;
        }

        if ($type === Transaction::TYPE_INCOME) {
            $salary = Category::where('name', 'GAJI')->first();

            if ($salary) {
                return $salary;
            }
        }

        return Category::firstOrCreate(
            ['name' => 'LAINNYA'],
            ['icon' => 'ðŸ“Œ', 'type' => Category::TYPE_BOTH, 'keywords' => []],
        );
    }

    private function isLimited(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        $body = Str::lower($response->body());

        return str_contains($body, 'rate limit')
            || str_contains($body, 'quota')
            || str_contains($body, 'resource_exhausted');
    }

    private function extractJson(string $content): string
    {
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            return $matches[0];
        }

        return $content;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Extract one Indonesian finance transaction from the user message.
Return only valid JSON with keys: response, description, amount, category, type.
amount must be an integer rupiah value or null.
type must be "expense", "income", or null.
category should be one of: MAKANAN, TRANSPORT, BELANJA, TAGIHAN, HIBURAN, GAJI, LAINNYA.
If the message has no amount, set description, amount, category, and type to null.
PROMPT;
    }
}
