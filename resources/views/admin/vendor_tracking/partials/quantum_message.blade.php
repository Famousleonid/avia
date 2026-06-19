@php
    $message = trim((string) ($line->apply_message ?? ''));
    if ($message === '') {
        $message = 'Not parsed yet.';
    }

    $importantPhrases = [
        'WO not found: old',
        'Workorder not found',
        'WO not found',
        'Unsupported Quantum PN',
        'Missing REF',
        'No STD process target',
        'Multiple STD process targets',
        'Vendor not found',
        'Bushing REF must be batch',
        'No bushing batches',
        'Bushing batch not found',
        'No process_names.code matched REF',
        'Multiple process_names.code matched REF',
        'No TDR process target',
        'Multiple TDR process targets',
        'No target process',
        'Applied',
        'Already current',
        'Dismissed by user',
        'Restored by user',
    ];

    $phrasePattern = implode('|', array_map(
        static fn (string $phrase): string => preg_quote($phrase, '/'),
        $importantPhrases
    ));
    $woNumber = trim((string) ($line->wo_number ?? ''));
    $woDigits = preg_replace('/\D+/', '', $woNumber);
    $woAlternates = [];

    if ($woDigits !== '' && strlen($woDigits) >= 5) {
        $woAlternates[] = 'W\s*' . preg_quote($woDigits, '/');
        $woAlternates[] = preg_quote($woDigits, '/');
    }

    if ($woNumber !== '') {
        array_unshift($woAlternates, preg_quote($woNumber, '/'));
    }

    $woFallbackPattern = '\bW\d{5,}\b';
    $woPattern = '(?:' . implode('|', array_unique(array_filter($woAlternates))) . ($woAlternates ? '|' : '') . $woFallbackPattern . ')';
    $combinedPattern = '/(' . $phrasePattern . '|' . $woPattern . ')/i';
    $woOnlyPattern = '/^' . $woPattern . '$/i';
    $parts = [];
    $offset = 0;

    if (preg_match_all($combinedPattern, $message, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            [$text, $position] = $match;

            if ($position > $offset) {
                $parts[] = [
                    'class' => 'quantum-message-muted',
                    'text' => substr($message, $offset, $position - $offset),
                ];
            }

            $parts[] = [
                'class' => preg_match($woOnlyPattern, $text) ? 'quantum-message-wo' : 'quantum-message-info',
                'text' => $text,
            ];
            $offset = $position + strlen($text);
        }
    }

    if ($offset < strlen($message)) {
        $parts[] = [
            'class' => 'quantum-message-muted',
            'text' => substr($message, $offset),
        ];
    }

    if ($parts === []) {
        $parts[] = [
            'class' => 'quantum-message-muted',
            'text' => $message,
        ];
    }
@endphp
@foreach($parts as $part)<span class="{{ $part['class'] }}">{{ $part['text'] }}</span>@endforeach
