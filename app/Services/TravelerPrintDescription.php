<?php

namespace App\Services;

final class TravelerPrintDescription
{
    /** Extract additional instructions without changing technical values or wording. */
    public static function additional(?string $description, ?string $specification, array $names, bool $preserveFigureReferences = false): string
    {
        // Reviewed workbook labels for the same technical process. Inspection stage,
        // polish/hone and embrittlement intent are NOT redundant process aliases.
        $aliases = [
            'Machining (AT)' => ['Machining'], 'Shot peening' => ['Shot peen'],
            'Chrome plating' => ['Chrome plate'], 'Chrome stripping' => ['Chrome strip'],
            'Cad plate' => ['Cad plating'], 'Cad stripping' => ['CAD strip'],
            'Nital Etch Inspection' => ['Nital etch'],
            'NDT-1' => ['NDT'], 'NDT-4' => ['NDT'],
            'NDT-6 Eddy Current' => ['Eddy Current', 'Eddy Current Test', 'Eddy Current Insp.'],
            'E - Nickel stripping' => ['Electroless Nickel Stripping'],
            'E - Nickel plating' => ['Electroless Nickel Plating'],
        ];
        foreach ($names as $name) $names = array_merge($names, $aliases[$name] ?? []);
        $text = str_replace(["\r\n", "\r"], "\n", (string) $description);
        $pattern = static function (string $value): string {
            $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
            return implode('[\\s\\x{00a0}]+', array_map(fn ($word) => preg_quote($word, '~'), $words));
        };
        // Template storage keeps standalone FIG references even when the selected
        // catalog entry consists of that same reference. Printing keeps its own deduplication.
        $figureOnlySpecification = $preserveFigureReferences
            && preg_match('/^(?:CMM\s+|SB[\d-]+[ ,;]*)?Fig(?:ure)?s?\b/i', trim((string) $specification));
        if (trim((string) $specification) !== '' && ! $figureOnlySpecification) {
            // A complete repeated block, allowing line wrapping but not different wording/numbers.
            $text = preg_replace('~(?:^|\n)[\h]*'.$pattern($specification).'(?=\s|$)[\h]*~iu', "\n", $text);
        }
        foreach (array_unique($names) as $name) {
            if (trim((string) $name) === '') continue;
            $namePattern = $pattern($name);
            $text = preg_replace('~^[\h]*'.$namePattern.'[\h]*[:;]?[\h]*$~imu', '', $text);
            // Keep qualifiers such as "(Outer and Inner Diameter)" as additional instructions.
            $text = preg_replace('~^[\h]*'.$namePattern.'[\h]+(?=\()~imu', '', $text);
        }
        return trim($text);
    }
}
