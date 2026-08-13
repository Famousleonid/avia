<?php

namespace App\Support;

use App\Models\ManualServiceBulletin;
use Illuminate\Support\Collection;

class ServiceBulletinPrintPaginator
{
    private const DEFAULT_FONT_SIZE_PT = 8.3;
    private const MIN_FONT_SIZE_PT = 6.0;
    private const MAX_FONT_SIZE_PT = 14.0;
    private const PRINTABLE_ROWS_HEIGHT_PX = 555.0;
    private const MIN_ROW_HEIGHT_PX = 52.0;
    private const BASE_LINE_HEIGHT_PX = 12.0;

    /**
     * @param  Collection<int, ManualServiceBulletin>  $bulletins
     * @return Collection<int, Collection<int, ManualServiceBulletin>>
     */
    public function paginate(Collection $bulletins, float $fontSizePt = self::DEFAULT_FONT_SIZE_PT): Collection
    {
        $pages = collect();
        $currentPage = collect();
        $currentHeight = 0.0;

        foreach ($bulletins->values() as $bulletin) {
            $rowHeight = $this->estimatedRowHeight($bulletin, $fontSizePt);
            $rowLimit = $pages->isEmpty() ? 10 : 9;
            $pageIsFull = $currentPage->isNotEmpty()
                && ($currentPage->count() >= $rowLimit
                    || $currentHeight + $rowHeight > self::PRINTABLE_ROWS_HEIGHT_PX);

            if ($pageIsFull) {
                $pages->push($currentPage);
                $currentPage = collect();
                $currentHeight = 0.0;
            }

            $currentPage->push($bulletin);
            $currentHeight += $rowHeight;
        }

        if ($currentPage->isNotEmpty()) {
            $pages->push($currentPage);
        }

        return $pages;
    }

    private function estimatedRowHeight(ManualServiceBulletin $bulletin, float $fontSizePt): float
    {
        $fontSizePt = min(self::MAX_FONT_SIZE_PT, max(self::MIN_FONT_SIZE_PT, $fontSizePt));
        $fontScale = $fontSizePt / self::DEFAULT_FONT_SIZE_PT;
        $lineHeight = self::BASE_LINE_HEIGHT_PX * $fontScale;

        $estimatedLines = max(
            $this->wrappedLineCount((string) $bulletin->year_introduced, $this->scaledCharactersPerLine(13, $fontScale)),
            $this->wrappedLineCount((string) $bulletin->ac_mfg_service_bulletin_no, $this->scaledCharactersPerLine(15, $fontScale)),
            $this->wrappedLineCount((string) $bulletin->oem_service_bulletin_no, $this->scaledCharactersPerLine(14, $fontScale)),
            $this->wrappedLineCount((string) $bulletin->awd_no, $this->scaledCharactersPerLine(14, $fontScale)),
            $this->wrappedLineCount((string) $bulletin->identification_method, $this->scaledCharactersPerLine(18, $fontScale)),
            $this->wrappedLineCount((string) $bulletin->description, $this->scaledCharactersPerLine(52, $fontScale))
        );

        return max(self::MIN_ROW_HEIGHT_PX, ($estimatedLines * $lineHeight) + 6.0);
    }

    private function scaledCharactersPerLine(int $baseCharacters, float $fontScale): int
    {
        return max(4, (int) floor($baseCharacters / $fontScale));
    }

    private function wrappedLineCount(string $value, int $charactersPerLine): int
    {
        $lines = preg_split('/\R/u', trim($value)) ?: [''];

        return max(1, array_sum(array_map(
            fn (string $line): int => max(1, (int) ceil(mb_strlen($line) / $charactersPerLine)),
            $lines
        )));
    }
}
