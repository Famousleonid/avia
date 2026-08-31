<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class NameplateRecognitionService
{
    /**
     * @param  array{expected_part_number?: string, expected_assy_part_number?: string}  $context
     * @return array{part_numbers: list<string>, serial_numbers: list<string>, confidence: string, notes: string}
     */
    public function recognize(string $imagePath, string $mimeType, array $context = []): array
    {
        $apiKey = trim((string) config('services.openai.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new RuntimeException('The saved Log Card photo cannot be read.');
        }

        $binary = file_get_contents($imagePath);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('The saved Log Card photo is empty.');
        }

        $mimeType = in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)
            ? $mimeType
            : 'image/jpeg';

        $knownContext = array_filter([
            trim((string) ($context['expected_part_number'] ?? '')) !== ''
                ? 'Expected row P/N: '.trim((string) $context['expected_part_number'])
                : null,
            trim((string) ($context['expected_assy_part_number'] ?? '')) !== ''
                ? 'Expected assembly P/N: '.trim((string) $context['expected_assy_part_number'])
                : null,
        ]);

        $prompt = <<<'PROMPT'
Read the aviation component nameplate, engraving, label, or stamped marking in this image.
Return only identifiers that are explicitly visible. Never complete, repair, or infer an obscured character from the expected values.
Part-number labels may look like P/N, PN, PART NO, PART NUMBER, or similar.
Serial-number labels may look like S/N, SN, SERIAL NO, SERIAL NUMBER, or similar, but a serial candidate does not need to have a label.
When an expected row P/N is supplied and that exact P/N is visible, treat another distinct prominent identifier on the same component as a serial-number candidate even when it is not explicitly labelled S/N. This is typing assistance: return the visible candidate for technician review instead of excluding it.
Do not classify identifiers by vertical position: the serial number may be above the part number or below it. Use visible labels, formatting, and component context instead.
If two distinct prominent identifiers are visible without clear labels, return your best P/N and S/N classification rather than omitting either; the technician can swap or edit them.
Text may follow a circular or curved surface. When two markings are separated by a visible gap around the circumference, read and return them as two separate identifiers; never concatenate across that gap.
Put every other clearly visible identifier that cannot be classified confidently into other_identifiers; do not leave a visible identifier only in notes.
Preserve punctuation, slashes, dashes, leading zeroes, and letter case exactly as visible.
If several plausible values are visible, return each candidate in reading order. Use an empty array when a value is not visible.
PROMPT;
        if ($knownContext !== []) {
            $prompt .= "\nContext is supplied only to identify the relevant row, not to guess unreadable characters:\n"
                .implode("\n", $knownContext);
        }

        $payload = [
            'model' => (string) config('services.openai.model', 'gpt-5.4'),
            'store' => false,
            'input' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $prompt],
                    [
                        'type' => 'input_image',
                        'image_url' => 'data:'.$mimeType.';base64,'.base64_encode($binary),
                        'detail' => 'high',
                    ],
                ],
            ]],
            'tools' => [[
                'type' => 'function',
                'name' => 'extract_nameplate_identifiers',
                'description' => 'Return all visible aviation identifiers, including unlabeled candidates, from the image.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'part_numbers' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'serial_numbers' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'other_identifiers' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'confidence' => [
                            'type' => 'string',
                            'enum' => ['high', 'medium', 'low'],
                        ],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['part_numbers', 'serial_numbers', 'other_identifiers', 'confidence', 'notes'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ]],
            'tool_choice' => 'required',
            'parallel_tool_calls' => false,
        ];

        $maxAttempts = max(1, min(4, (int) config('services.openai.retry_attempts', 4)));
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.openai.timeout_seconds', 120))
                ->post('https://api.openai.com/v1/responses', $payload);

            if ($response->successful()) {
                return $this->extractResult((array) $response->json(), $context);
            }

            if (($response->status() >= 500 || $response->status() === 429) && $attempt < $maxAttempts) {
                usleep((250 * (2 ** ($attempt - 1))) * 1000);
                continue;
            }

            throw new RuntimeException('OpenAI API error while reading the Log Card photo.');
        }

        throw new RuntimeException('OpenAI API request failed after retries.');
    }

    /** @return array{part_numbers: list<string>, serial_numbers: list<string>, confidence: string, notes: string} */
    private function extractResult(array $response, array $context = []): array
    {
        foreach ((array) ($response['output'] ?? []) as $item) {
            if (($item['type'] ?? null) !== 'function_call'
                || ($item['name'] ?? null) !== 'extract_nameplate_identifiers') {
                continue;
            }

            $arguments = json_decode((string) ($item['arguments'] ?? ''), true);
            if (! is_array($arguments)) {
                break;
            }

            $partNumbers = $this->cleanCandidates($arguments['part_numbers'] ?? []);
            $serialNumbers = $this->cleanCandidates($arguments['serial_numbers'] ?? []);

            $expectedNumbers = $this->cleanCandidates([
                $context['expected_part_number'] ?? '',
                $context['expected_assy_part_number'] ?? '',
            ]);
            if ($serialNumbers === []) {
                $expectedNormalized = array_map($this->normalizeIdentifier(...), $expectedNumbers);
                $fallbackCandidates = $this->cleanCandidates($arguments['other_identifiers'] ?? []);
                if ($expectedNumbers !== []) {
                    $fallbackCandidates = $this->cleanCandidates(array_merge($fallbackCandidates, $partNumbers));
                }

                $serialNumbers = array_values(array_filter(
                    $fallbackCandidates,
                    fn (string $candidate): bool => ! in_array(
                        $this->normalizeIdentifier($candidate),
                        $expectedNormalized,
                        true,
                    ),
                ));
            }

            return [
                'part_numbers' => $partNumbers,
                'serial_numbers' => $serialNumbers,
                'confidence' => in_array(($arguments['confidence'] ?? ''), ['high', 'medium', 'low'], true)
                    ? $arguments['confidence']
                    : 'low',
                'notes' => mb_substr(trim((string) ($arguments['notes'] ?? '')), 0, 500),
            ];
        }

        throw new RuntimeException('The image response did not contain structured nameplate identifiers.');
    }

    /** @return list<string> */
    private function cleanCandidates(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn ($value): bool => is_scalar($value))
            ->map(fn ($value): string => mb_substr(trim((string) $value), 0, 255))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->all();
    }

    private function normalizeIdentifier(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($value)) ?? '';
    }
}
