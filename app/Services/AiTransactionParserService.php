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
        // Multi‑transaction support
        if (isset($payload['transactions']) && is_array($payload['transactions'])) {
            $transactions = [];
            foreach ($payload['transactions'] as $tx) {
                $amountRaw = Arr::get($tx, 'amount');
                // Convert amount strings with dot separators (e.g., "13.000")
                if (is_string($amountRaw)) {
                    $amountRaw = preg_replace('/[^0-9]/', '', $amountRaw);
                }
                $amount = (int) $amountRaw;
                $type = Arr::get($tx, 'type');
                if (! is_numeric($amount) || $amount <= 0 || ! in_array($type, [Transaction::TYPE_EXPENSE, Transaction::TYPE_INCOME], true)) {
                    // skip invalid transaction
                    continue;
                }
                $transactions[] = [
                    'description' => $this->cleanDescription($this->nullableString(Arr::get($tx, 'description'))),
                    'amount' => $amount,
                    'category' => $this->resolveCategory($this->nullableString(Arr::get($tx, 'category')), $type),
                    'type' => $type,
                ];
            }
            return [
                'transactions' => $transactions,
                'response' => $this->nullableString(Arr::get($payload, 'response')),
            ];
        }
        // Single transaction fallback (original behaviour)
        $amountRaw = Arr::get($payload, 'amount');
        if (is_string($amountRaw)) {
            $amountRaw = preg_replace('/[^0-9]/', '', $amountRaw);
        }
        $amount = (int) $amountRaw;
        $type = Arr::get($payload, 'type');
        if (! is_numeric($amount) || $amount <= 0 || ! in_array($type, [Transaction::TYPE_EXPENSE, Transaction::TYPE_INCOME], true)) {
            return [
                'description' => null,
                'amount' => null,
                'category' => null,
                'type' => null,
                'response' => $this->nullableString(Arr::get($payload, 'response')),
            ];
        }
        return [
            'description' => $this->cleanDescription($this->nullableString(Arr::get($payload, 'description'))),
            'amount' => $amount,
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

    private function cleanDescription(?string $description): ?string
    {
        if ($description === null || trim($description) === '') {
            return null;
        }
        $fillers = ['beli', 'bayar', 'makan', 'minum', 'buat', 'untuk', 'pengeluaran', 'pemasukan', 'isi', 'top up'];
        $words = preg_split('/\s+/', trim($description));
        if (count($words) > 0 && in_array(Str::lower($words[0]), $fillers, true)) {
            array_shift($words);
        }
        $result = trim(implode(' ', $words));
        return $result === '' ? $description : Str::ucfirst($result);
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

        // HEREDOC (tanpa kutip) supaya {$categories} ter-interpolate dengan daftar
        // kategori asli dari DB. Pakai NOWDOC ('PROMPT') = AI cuma terima teks literal
        // {$categories} dan tidak tahu kategori apa yang tersedia → sering salah ke LAINNYA.
        return <<<PROMPT
You are ZAKU AI, an Indonesian personal finance assistant.
Your task: Parse user message into one finance transaction.

## CATEGORIES AVAILABLE (use EXACTLY one of these names in "category")
{$categories}

## AMOUNT PARSING RULES
- "13rb", "13k", "13rb aja", "13k aja" → 13000
- "1jt", "1juta" → 1000000
- "250.000", "250000" → 250000
- "1,5jt", "1,5 juta" → 1500000
- "20 ribu" → 20000
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

## CATEGORY MAPPING (map the ITEM to its category, NOT the verb)
1. MAKANAN: semua makanan & minuman — kopi, teh, roti, nasi, gorengan, warteg,
   kwetiau, mie, bakso, ayam, snack, jajan, bubur, sate, gado-gado, es, jus, makan siang
2. TRANSPORTASI: grab, gojek, ojol, taxi, bensin, parkir, tol, bus, kereta, pesawat, bbm
3. TELEKOMUNIKASI: pulsa, paket data, axis, telkomsel, xl, indosat, pln token
4. HIBURAN: netflix, spotify, youtube, film, nonton, game, konser
5. KESEHATAN: obat, apotek, dokter, rs, vitamin, klinik
6. KECANTIKAN: shampoo, sabun, parfum, skincare, salon, cukur
7. PENDIDIKAN: buku, les, kursus, sekolah, spp
8. TAGIHAN: listrik, air, internet, bpjs, gas, iuran
9. BELANJA: belanja bulanan, baju, sepatu, tokopedia, shopee, supermarket
10. RUMAH TANGGA: sabun cuci, tisu, alat rumah, kontrakan, kos
11. GAJI: salary, gajian, paycheck (INCOME)
12. LAINNYA: HANYA kalau benar-benar tidak ada kategori yang cocok (mis. transfer ke orang)

Rules:
- ALWAYS prefer a specific category over LAINNYA.
- If the item is food/drink but not in the list above, still use MAKANAN.

## TYPO HANDLING
- If the description contains a typo, FIX it to the correct Indonesian spelling.
  Example: "kweticaw" / "kwetiaw" / "kwetiauw" → "Kwetiau"
- Do NOT invent a new spelling — correct to the real word.

## OUTPUT FORMAT
Return ONLY valid JSON (no extra text):
{
  "description": "string (max 50 chars, Indonesian, typo corrected)",
  "amount": integer (rupiah, > 0),
  "category": "string (EXACTLY one from CATEGORIES AVAILABLE above)",
  "type": "expense" or "income",
  "response": "string (friendly confirmation in Indonesian)"
}

## EDGE CASES
- No amount → {"description":null,"amount":null,"category":null,"type":null,"response":" berapa jumlahnya?"}
- No clear item → {"description":null,"amount":null,"category":null,"type":null,"response":" beli/bayar apa?"}

## EXAMPLES
Input: "Beli kopi 13rb"
Output: {"description":"Kopi","amount":13000,"category":"MAKANAN","type":"expense","response":"Oke, dicatat! Pengeluaran kopi Rp13.000"}

Input: "Kwetiacw 20 ribu"
Output: {"description":"Kwetiau","amount":20000,"category":"MAKANAN","type":"expense","response":"Oke, dicatat! Pengeluaran Kwetiau Rp20.000"}

Input: "Bensin 25rb"
Output: {"description":"Bensin","amount":25000,"category":"TRANSPORTASI","type":"expense","response":"Oke, dicatat! Pengeluaran bensin Rp25.000"}

Input: "Transfer ke ibu 200rb"
Output: {"description":"Transfer ke ibu","amount":200000,"category":"LAINNYA","type":"expense","response":"Oke, dicatat! Pengeluaran transfer ke ibu Rp200.000"}
PROMPT;
    }
}
