<?php

namespace App\Services\Measurements;

use App\Models\ProcessDocument;
use App\Models\WoMeasurement;
use App\Models\Workorder;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Stage 2c.1 — render a process document template into a concrete WO PDF.
 *
 * Substitutes:
 *   - static dimensions  → as-is
 *   - measurement dims   → final actual_value of source_parameter in this WO
 *   - placeholders       → WO data ({wo_number}, {serial_number}, ...)
 *
 * Produces one multi-page PDF (HTML page-break → dompdf).
 */
class ProcessDocumentRenderer
{
    /**
     * @param array $context  optional ['repair_number' => string, 'component_pn' => string]
     * @return string PDF binary
     */
    public function render(ProcessDocument $document, Workorder $workorder, array $context = []): string
    {
        $document->loadMissing('pages.elements');

        $pages = $document->pages->map(fn($p) => $this->renderPage($p, $workorder, $context))->all();

        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><style>'
            . '@page{margin:8mm;}'
            . 'html,body{margin:0;padding:0;font-family:Arial,sans-serif;}'
            . '.pdw-page{position:relative;page-break-after:always;}'
            . '.pdw-page:last-child{page-break-after:auto;}'
            . '.pdw-page img{width:100%;display:block;}'
            . '.pdw-el{position:absolute;transform:translate(-50%,-50%);font-size:9pt;font-weight:700;white-space:nowrap;}'
            . '.pdw-dim{color:#0d6efd;background:#fff;border:1px solid #0d6efd;border-radius:2px;padding:0 3px;}'
            . '.pdw-label{color:#0d9488;}'
            . '</style></head><body>'
            . implode('', $pages)
            . '</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }

    private function renderPage($page, Workorder $workorder, array $context): string
    {
        $img = $this->imageDataUri($page->image_path);
        $imgTag = $img ? '<img src="' . $img . '" alt="">' : '<div style="height:200px"></div>';

        $els = '';
        foreach ($page->elements as $e) {
            [$xp, $yp] = $this->elementPosition($e);
            if ($xp === null) {
                continue;
            }
            $cls = $e->element_type === 'dimension' ? 'pdw-el pdw-dim' : 'pdw-el pdw-label';
            $text = htmlspecialchars($this->resolveValue($e, $workorder, $context), ENT_QUOTES, 'UTF-8');
            $els .= '<div class="' . $cls . '" style="left:' . $xp . '%;top:' . $yp . '%">' . $text . '</div>';
        }

        return '<div class="pdw-page">' . $imgTag . $els . '</div>';
    }

    private function elementPosition($e): array
    {
        if ($e->element_type === 'dimension') {
            if ($e->label_x_pct !== null) {
                return [(float) $e->label_x_pct, (float) $e->label_y_pct];
            }
            if ($e->mask === 'linear' && $e->x2_pct !== null) {
                return [((float) $e->x_pct + (float) $e->x2_pct) / 2, ((float) $e->y_pct + (float) $e->y2_pct) / 2];
            }
        }
        if ($e->x_pct === null) {
            return [null, null];
        }
        return [(float) $e->x_pct, (float) $e->y_pct];
    }

    private function resolveValue($e, Workorder $workorder, array $context): string
    {
        if ($e->element_type === 'dimension') {
            $prefix = $e->mask === 'diameter' ? 'Ø' : '';
            if ($e->value_source === 'measurement') {
                $v = $this->measurementValue($workorder->id, $e->source_parameter_id);
                return $prefix . ($v !== null ? $this->fmt($v) : '—');
            }
            return $prefix . ($e->static_value !== null ? $this->fmt($e->static_value) : '');
        }

        // label / text
        if (!empty($e->placeholder)) {
            return $this->resolvePlaceholder($e->placeholder, $workorder, $context);
        }
        return (string) ($e->text ?? '');
    }

    /** Final actual_value of a parameter in this WO (fallback: latest any stage). */
    private function measurementValue(int $workorderId, ?int $parameterId): ?float
    {
        if (!$parameterId) {
            return null;
        }
        $m = WoMeasurement::where('workorder_id', $workorderId)
                ->where('manual_parameter_id', $parameterId)
                ->where('stage', 'final')
                ->whereNotNull('actual_value')
                ->latest('id')->first()
            ?? WoMeasurement::where('workorder_id', $workorderId)
                ->where('manual_parameter_id', $parameterId)
                ->whereNotNull('actual_value')
                ->latest('id')->first();

        return $m?->actual_value !== null ? (float) $m->actual_value : null;
    }

    private function resolvePlaceholder(string $ph, Workorder $workorder, array $context): string
    {
        switch ($ph) {
            case '{wo_number}':      return (string) ($workorder->number ?? '');
            case '{serial_number}':  return (string) ($workorder->unit?->serial_number ?? $workorder->serial_number ?? '');
            case '{repair_number}':  return (string) ($context['repair_number'] ?? '');
            case '{component_pn}':   return (string) ($context['component_pn'] ?? '');
            case '{date}':           return now()->format('d/M/Y');
            default:                 return $ph;
        }
    }

    private function fmt($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
    }

    /** Convert a stored image route URL into a base64 data URI for reliable dompdf embedding. */
    private function imageDataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#/image/show/big/(\d+)#', $path, $m)) {
            $media = Media::find((int) $m[1]);
            if ($media && file_exists($media->getPath())) {
                return 'data:' . $media->mime_type . ';base64,' . base64_encode(file_get_contents($media->getPath()));
            }
        }
        return null;
    }
}
