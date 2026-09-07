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
        $categories = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $cat) => $cat->name)
            ->implode(', ');

        return <<<'PROMPT'
You are ZAKU AI, an Indonesian personal finance assistant.
Your task: Parse user message into one finance transaction.

## CATEGORIES AVAILABLE
{$categories}

## AMOUNT PARSING RULES
- "13rb", "13k", "13rb aja", "13k aja" → 13000
- "1jt", "1juta" → 1000000
- "250.000", "250000" → 250000
- "1,5jt", "1,5 juta" → 1500000
- "bayarrr", "rugiii", "aja/aj" → parse as normal word

## TYPE DETECTION (context-aware)
INCOME if message contains: dapat, dapet, dpt, gaji, salary, bonus, dibayar,
transfer masuk, pendapatan, fee, untung, bayaran, dapat bayaran, transfer dari

EXPENSE if message contains: beli, bayar, jajan, nge-trf, top up, trf ke,
transfer ke, sewa, tagihan, potong, cicilan, makan, grab, gojek, beliin

Context rules:
- "transfer ke [orang]" → EXPENSE
- "transfer dari [orang]" → INCOME
- "bayar utang ke [orang]" → EXPENSE
- "dapat utang dari [orang]" → INCOME

## CATEGORY MAPPING (priority order)
1. TELEKOMUNIKASI: pulsa, axis, telkomsel, xl, indosat, paket data
2. TRANSPORTASI: grab, gojek, bjrt, taxi, parkir, tol, bensin, ojol
3. HIBURAN: netflix, spotify, youtube, film, nonton, game
4. KESEHATAN: obat, apotek, dokter, rs, vitamin
5. KECANTIKAN: shampoo, sabun, parfum, skincare, salon
6. PENDIDIKAN: buku, les, kursus, sekolah
7. MAKANAN: makan, minum, kopi, teh, roti, gorengan, jajan, warteg
8. TAGIHAN: listrik, air, internet, bpjs
9. GAJI: salary, gajian, paycheck
10. LAINNYA: default (fallback)

## OUTPUT FORMAT
Return ONLY valid JSON (no extra text):
{
  "description": "string (max 50 chars, Indonesian)",
  "amount": integer (rupiah, > 0),
  "category": "string (from available categories)",
  "type": "expense" or "income",
  "response": "string (friendly confirmation in Indonesian)"
}

## EDGE CASES
- No amount → {"description":null,"amount":null,"category":null,"type":null,"response":" berapa jumlahnya?"}
- No clear item → {"description":null,"amount":null,"category":null,"type":null,"response":" beli/bayar apa?"}
- Typo → still parse (don't fail)

## EXAMPLE OUTPUTS
Input: "Beli kopi 13rb"
Output: {"description":"Beli kopi","amount":13000,"category":"MAKANAN","type":"expense","response":"Oke, dicatat! Pengeluaran beli kopi Rp13.000"}

Input: "Grab 18rb"
Output: {"description":"Grab","amount":18000,"category":"TRANSPORTASI","type":"expense","response":"Oke, dicatat! Pengeluaran grab Rp18.000"}

Input: "Transfer ke ibu 200rb"
Output: {"description":"Transfer ke ibu","amount":200000,"category":"LAINNYA","type":"expense","response":"Oke, dicatat! Pengeluaran transfer ke ibu Rp200.000"}
PROMPT;
    }
}
