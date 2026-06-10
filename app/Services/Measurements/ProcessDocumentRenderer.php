<?php

namespace App\Services\Measurements;

use App\Models\ManualParameter;
use App\Models\ProcessDocument;
use App\Models\WoMeasurement;
use App\Models\Workorder;
use App\Services\Measurements\FormulaEvaluator;
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
     * @param array    $context          optional ['repair_number' => string, 'component_pn' => string]
     * @param int|null $onlyParameterId  EC: render only the pages of this place (parameter) — one PDF per place
     * @return string PDF binary
     */
    public function render(ProcessDocument $document, Workorder $workorder, array $context = [], ?int $onlyParameterId = null): string
    {
        $document->loadMissing('pages.elements');

        $pages = $document->pages;
        if ($onlyParameterId !== null) {
            $pages = $pages->where('parameter_id', $onlyParameterId)->values();
        }

        // "Own" parameter of this document (the drawing's point/parameter) — used by 'calc'
        // to derive the mating dimension from the F&C pair's nominal clearance. For a
        // per-place EC render it's the place itself; otherwise via documentable→rule.
        $docParam = $onlyParameterId
            ? ManualParameter::find($onlyParameterId)
            : $this->documentParameter($document);

        $pages = $pages->map(fn($p) => $this->renderPage($p, $workorder, $context, $docParam))->all();

        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><style>'
            . '@page{margin:8mm;}'
            . 'html,body{margin:0;padding:0;font-family:Arial,sans-serif;}'
            . '.pdw-page{position:relative;page-break-after:always;}'
            . '.pdw-page:last-child{page-break-after:auto;}'
            . '.pdw-page img{width:100%;display:block;}'
            . '.pdw-svg{position:absolute;top:0;left:0;width:100%;height:100%;}'
            . '.pdw-dot{position:absolute;width:5px;height:5px;margin:-2.5px 0 0 -2.5px;background:#0d9488;border-radius:50%;}'
            . '.pdw-el{position:absolute;transform:translate(-50%,-50%);font-size:9pt;font-weight:700;white-space:nowrap;}'
            . '.pdw-dim{color:#0d6efd;background:#fff;border:1px solid #0d6efd;border-radius:2px;padding:0 3px;}'
            . '.pdw-label{color:#0d9488;}'
            . '</style></head><body>'
            . implode('', $pages)
            . '</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }

    /**
     * Render a single ProcessDocumentPage as a standalone HTML string (with direct image src).
     * Used for browser preview (not PDF).
     */
    public function renderSinglePageHtml($page, Workorder $workorder, array $context = [], ?ManualParameter $docParam = null): string
    {
        $page->loadMissing('elements');
        $pageHtml = $this->renderPage($page, $workorder, $context, $docParam, true);

        return '<div class="pdw-page">' . $pageHtml . '</div>';
    }

    /**
     * Check which dimension elements have unresolvable values (missing measurements).
     * Returns [] when all values are available (drawing can be built).
     * Returns array of missing-value descriptors otherwise.
     *
     * Each descriptor:
     *   mask        — 'diameter' | 'radius' | 'linear' | ''
     *   param_id    — ManualParameter id whose measurement is absent
     *   param_desc  — human-readable parameter description
     *   point_code  — dimension-point code (e.g. "AA3"), or null
     *   point_desc  — dimension-point description, or null
     *   ic_label    — inspection-component label (part name), or null
     */
    public function getMissingValues($page, Workorder $workorder, array $context = [], ?ManualParameter $docParam = null): array
    {
        $page->loadMissing('elements');
        $missing = [];

        foreach ($page->elements as $e) {
            if ($e->element_type !== 'dimension') continue;

            // Re-use existing resolver — it already applies odStepFallback.
            $rawText = $this->resolveValue($e, $workorder, $context, $docParam);
            if (!$this->isEmptyDimValue($rawText)) continue;

            // For formula elements check each [p:ID] individually
            if ($e->value_source === 'formula') {
                $expr = trim((string) ($e->formula_expression ?? ''));
                preg_match_all('/\[p:(\d+)\]/', $expr, $m);
                foreach (array_unique(array_map('intval', $m[1])) as $pid) {
                    if ($this->measurementValue($workorder->id, $pid) === null) {
                        $missing[] = $this->buildMissingEntry($e, $pid);
                    }
                }
            } else {
                $missing[] = $this->buildMissingEntry($e, $e->source_parameter_id);
            }
        }

        // Deduplicate by param_id — one missing measurement, one line
        $seen = [];
        return array_values(array_filter($missing, function ($mv) use (&$seen) {
            $key = $mv['param_id'] ?? ('_' . $mv['param_desc']);
            if (isset($seen[$key])) return false;
            $seen[$key] = true;
            return true;
        }));
    }

    private function buildMissingEntry($e, ?int $paramId): array
    {
        $param = $paramId
            ? ManualParameter::with([
                'points:id,code,description',
                'inspectionComponent:id,label',
              ])->find($paramId)
            : null;

        $point = $param?->points?->first();
        $ic    = $param?->inspectionComponent;

        return [
            'mask'       => $e->mask ?? '',
            'param_id'   => $paramId,
            'param_desc' => $param?->description ?? ('param #' . $paramId),
            'point_code' => $point?->code,
            'point_desc' => $point?->description,
            'ic_label'   => $ic?->label,
        ];
    }

    private function renderPage($page, Workorder $workorder, array $context, ?ManualParameter $docParam, bool $directSrc = false): string
    {
        if ($directSrc) {
            $imgTag = $page->image_path ? '<img src="' . htmlspecialchars($page->image_path, ENT_QUOTES) . '" alt="">' : '<div style="height:200px"></div>';
        } else {
            $img = $this->imageDataUri($page->image_path);
            $imgTag = $img ? '<img src="' . $img . '" alt="">' : '<div style="height:200px"></div>';
        }

        $els   = '';
        $lines = '';
        $hasDimLine = false;
        foreach ($page->elements as $e) {
            // Resolve value first — skip element entirely if nothing to show
            $rawText = $this->resolveValue($e, $workorder, $context, $docParam);
            if ($e->element_type === 'dimension' && $this->isEmptyDimValue($rawText)) {
                continue; // no value — skip
            }

            // leader (label) / dimension lines → SVG overlay; anchor dot for labels
            if ($e->element_type === 'label' && $e->label_x_pct !== null && $e->x_pct !== null) {
                $lines .= '<line id="dim-leader-' . (int) $e->id . '"'
                    . ' x1="' . (float) $e->x_pct . '" y1="' . (float) $e->y_pct
                    . '" x2="' . (float) $e->label_x_pct . '" y2="' . (float) $e->label_y_pct
                    . '" stroke="#0d9488" stroke-width="0.12" />';
                $els .= '<div class="pdw-dot" style="left:' . (float) $e->x_pct . '%;top:' . (float) $e->y_pct . '%"></div>';
            }
            if ($e->element_type === 'dimension') {
                if ($e->x2_pct !== null) {
                    // Linear dimension line with arrowhead
                    $lines .= '<line x1="' . (float) $e->x_pct . '" y1="' . (float) $e->y_pct
                        . '" x2="' . (float) $e->x2_pct . '" y2="' . (float) $e->y2_pct
                        . '" stroke="#0d6efd" stroke-width="0.15" marker-end="url(#dim-arr)" />';
                    $hasDimLine = true;

                    // Leader: nearest point on dim line → label
                    if ($e->label_x_pct !== null) {
                        [$nx, $ny] = $this->nearestOnSegment(
                            (float) $e->label_x_pct, (float) $e->label_y_pct,
                            (float) $e->x_pct, (float) $e->y_pct,
                            (float) $e->x2_pct, (float) $e->y2_pct
                        );
                        // Store dim-line endpoints so JS can recalculate nearest point on drag
                        $lines .= '<line id="dim-leader-' . (int) $e->id . '"'
                            . ' data-lx1="' . (float) $e->x_pct  . '" data-ly1="' . (float) $e->y_pct
                            . '" data-lx2="' . (float) $e->x2_pct . '" data-ly2="' . (float) $e->y2_pct . '"'
                            . ' x1="' . $nx . '" y1="' . $ny
                            . '" x2="' . (float) $e->label_x_pct . '" y2="' . (float) $e->label_y_pct
                            . '" stroke="#0d6efd" stroke-width="0.12" />';
                    }
                } elseif ($e->label_x_pct !== null && $e->x_pct !== null) {
                    // Callout-style dimension (Ø, R) — anchor dot + leader to label
                    $els .= '<div class="pdw-dot" style="left:' . (float) $e->x_pct . '%;top:' . (float) $e->y_pct . '%;background:#0d6efd"></div>';
                    $lines .= '<line id="dim-leader-' . (int) $e->id . '"'
                        . ' x1="' . (float) $e->x_pct . '" y1="' . (float) $e->y_pct
                        . '" x2="' . (float) $e->label_x_pct . '" y2="' . (float) $e->label_y_pct
                        . '" stroke="#0d6efd" stroke-width="0.12" />';
                }
            }

            [$xp, $yp] = $this->elementPosition($e);
            if ($xp === null) {
                continue;
            }
            $cls = $e->element_type === 'dimension' ? 'pdw-el pdw-dim' : 'pdw-el pdw-label';
            $fs   = $e->font_size ? ';font-size:' . (int) $e->font_size . 'pt' : '';
            $text = htmlspecialchars($rawText, ENT_QUOTES, 'UTF-8');
            $els .= '<div class="' . $cls . '" data-element-id="' . (int) $e->id . '" style="left:' . $xp . '%;top:' . $yp . '%' . $fs . '">' . $text . '</div>';
        }

        $defs = $hasDimLine
            ? '<defs><marker id="dim-arr" viewBox="0 0 6 6" refX="5" refY="3" markerWidth="2.5" markerHeight="2.5" orient="auto"><path d="M0,0 L6,3 L0,6 z" fill="#0d6efd"/></marker></defs>'
            : '';
        $svg = $lines !== ''
            ? '<svg class="pdw-svg" viewBox="0 0 100 100" preserveAspectRatio="none">' . $defs . $lines . '</svg>'
            : '';

        return '<div class="pdw-page">' . $imgTag . $svg . $els . '</div>';
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
        } elseif ($e->label_x_pct !== null) {
            // label: the text box sits at label_x/label_y (on a leader from the anchor)
            return [(float) $e->label_x_pct, (float) $e->label_y_pct];
        }
        if ($e->x_pct === null) {
            return [null, null];
        }
        return [(float) $e->x_pct, (float) $e->y_pct];
    }

    private function resolveValue($e, Workorder $workorder, array $context, ?ManualParameter $docParam = null): string
    {
        if ($e->element_type === 'dimension') {
            $prefix = $e->mask === 'diameter' ? 'Ø' : ($e->mask === 'radius' ? 'R' : '');

            // formula — arbitrary arithmetic expression with [p:ID] parameter refs
            if ($e->value_source === 'formula') {
                return $prefix . $this->calcFormula($e, $workorder);
            }
            // 2c.2 — derived dimension from the F&C mating measurement.
            if ($e->value_source === 'calc') {
                $result = $this->calcMatingRange($e, $workorder, $docParam);
                if ($result === '—') {
                    $result = $this->odStepFallback($context, $docParam, $e);
                }
                return $prefix . $result;
            }
            if ($e->value_source === 'measurement') {
                // Bushing sketch: the required OD range from context takes
                // precedence — the drawing must show the size to MAKE, not the
                // raw mating bore measurement.
                if (!empty($context['od_override'])) {
                    $fb = $this->odStepFallback($context, $docParam, $e);
                    if ($fb !== '—') return $prefix . $fb;
                }
                $v = $this->measurementValue($workorder->id, $e->source_parameter_id);
                if ($v === null) {
                    $fallback = $this->odStepFallback($context, $docParam, $e);
                    return $prefix . $fallback;
                }
                return $prefix . $this->fmt($v);
            }
            return $prefix . ($e->static_value !== null ? $this->fmt($e->static_value) : '');
        }

        // label / text
        if (!empty($e->placeholder)) {
            return $this->resolvePlaceholder($e->placeholder, $workorder, $context);
        }
        // label bound to a parameter → its identifier "code · description" (e.g. AA3 · ID 11-10)
        if (!empty($e->source_parameter_id)) {
            return $this->parameterLabel((int) $e->source_parameter_id);
        }
        // Free text — also resolve any embedded {placeholder} tokens
        $text = (string) ($e->text ?? '');
        if ($text !== '' && str_contains($text, '{')) {
            $text = preg_replace_callback('/\{[a-z_]+\}/', function ($m) use ($workorder, $context) {
                return $this->resolvePlaceholder($m[0], $workorder, $context);
            }, $text);
        }

        return $text;
    }

    /**
     * formula: evaluate formula_expression substituting [p:ID] tokens with
     * actual final measurements, then apply ± formula_tolerance.
     *
     * Example element:
     *   formula_expression = "0.7128 - [p:45]"
     *   formula_tolerance  = 0.0039
     * Result displayed as "0.2717–0.2795" (lo–hi).
     */
    private function calcFormula($e, Workorder $workorder): string
    {
        $expr = trim((string) ($e->formula_expression ?? ''));
        if ($expr === '') {
            return '—';
        }

        // Collect all [p:ID] tokens and fetch their measured values
        preg_match_all('/\[p:(\d+)\]/', $expr, $matches);
        $paramIds    = array_unique(array_map('intval', $matches[1]));
        $paramValues = [];
        foreach ($paramIds as $pid) {
            $paramValues[$pid] = $this->measurementValue($workorder->id, $pid);
        }

        try {
            $result = FormulaEvaluator::evaluate($expr, $paramValues);
        } catch (\RuntimeException $ex) {
            return '?';
        }

        if ($result === null) {
            return '—'; // one or more measurements not yet recorded
        }

        $plus  = $e->formula_tol_plus  !== null ? (float) $e->formula_tol_plus  : 0.0;
        $minus = $e->formula_tol_minus !== null ? (float) $e->formula_tol_minus : 0.0;

        if ($plus > 0 || $minus > 0) {
            $lo = $this->fmt($result - $minus);
            $hi = $this->fmt($result + $plus);
            return $lo . '–' . $hi;
        }

        return $this->fmt($result);
    }

    /**
     * 2c.2 calc: target dimension range for THIS drawing's part, derived from the
     * actual measurement of the mating parameter (source_parameter_id) and the
     * nominal clearance of the F&C pair.
     *
     *   nominal clearance = mating − this  (sign auto-handles clearance vs interference)
     *     c_min = mating.orig_min − this.orig_max
     *     c_max = mating.orig_max − this.orig_min
     *   target ∈ [measured − c_max, measured − c_min]
     */
    private function calcMatingRange($e, Workorder $workorder, ?ManualParameter $docParam): string
    {
        $measured = $this->measurementValue($workorder->id, $e->source_parameter_id);
        if ($measured === null) {
            return '—';
        }

        $mating = $e->source_parameter_id ? ManualParameter::find($e->source_parameter_id) : null;
        if (!$docParam || !$mating
            || $docParam->orig_dim_min === null || $docParam->orig_dim_max === null
            || $mating->orig_dim_min === null || $mating->orig_dim_max === null) {
            // not enough data to derive — fall back to the raw measurement
            return $this->fmt($measured);
        }

        $cMin = (float) $mating->orig_dim_min - (float) $docParam->orig_dim_max;
        $cMax = (float) $mating->orig_dim_max - (float) $docParam->orig_dim_min;

        $lo = $measured - $cMax;
        $hi = $measured - $cMin;

        return $this->fmt($lo) . '–' . $this->fmt($hi);
    }

    /** The parameter a (Main) document belongs to, via documentable → rule → parameter. */
    private function documentParameter(ProcessDocument $document): ?ManualParameter
    {
        $documentable = $document->documentable;            // ManualParameterRuleProcess | MasterRulePhaseRuleProcess
        $rule = $documentable?->rule ?? null;               // only Main rule-processes expose ->rule->parameter
        return $rule?->parameter;
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

    /** Identifier of a parameter for a bound label: "code · description" (e.g. AA3 · ID 11-10). */
    private function parameterLabel(int $parameterId): string
    {
        $p = ManualParameter::with('points:id,code')->find($parameterId);
        if (!$p) {
            return '';
        }
        $codes = $p->points->pluck('code')->filter()->implode(', ');
        $desc  = (string) ($p->description ?? '');

        return $codes !== '' ? trim($codes . ' · ' . $desc) : $desc;
    }

    private function resolvePlaceholder(string $ph, Workorder $workorder, array $context): string
    {
        switch ($ph) {
            case '{wo_number}':       return $workorder->number ? 'W' . $workorder->number : '';
            case '{serial_number}':   return (string) ($workorder->unit?->serial_number ?? $workorder->serial_number ?? '');
            case '{repair_number}':   return (string) ($context['repair_number'] ?? '');
            case '{component_pn}':    return (string) ($context['component_pn'] ?? '');
            case '{technician_name}': return (string) ($workorder->user?->name ?? '');
            case '{manual_number}':   return (string) ($workorder->unit?->manuals?->number ?? '');
            case '{manual_lib}':      return (string) ($workorder->unit?->manuals?->lib ?? '');
            case '{date}':            return now()->format('d/M/Y');
            default:                  return $ph;
        }
    }

    /**
     * Nearest point on segment P1→P2 to point P0.
     * Returns [x, y] as rounded floats (2 decimals).
     */
    /**
     * True when a resolved dimension value is "empty" — i.e. no real value available.
     * Handles: '' | '—' | 'Ø—' | 'R—' | '?' | 'Ø?' | 'R?'
     */
    private function isEmptyDimValue(string $text): bool
    {
        $core = ltrim($text, 'ØR');   // strip optional prefix
        return $core === '' || $core === '—' || $core === '?';
    }

    private function nearestOnSegment(float $px, float $py, float $x1, float $y1, float $x2, float $y2): array
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $lenSq = $dx * $dx + $dy * $dy;
        if ($lenSq < 1e-10) {
            return [round($x1, 2), round($y1, 2)];
        }
        $t = (($px - $x1) * $dx + ($py - $y1) * $dy) / $lenSq;
        $t = max(0.0, min(1.0, $t));
        return [round($x1 + $t * $dx, 2), round($y1 + $t * $dy, 2)];
    }

    /**
     * Fallback for OD dimension elements that can't resolve from measurement/calc:
     * if context carries repair-step OD range AND the element belongs to the doc parameter
     * (or has no source_parameter_id), return "dim_min–dim_max".
     */
    private function odStepFallback(array $context, ?ManualParameter $docParam, $e): string
    {
        if (!isset($context['od_dim_min'], $context['od_dim_max'])) {
            return '—';
        }
        // Apply only when the element is associated with the document's own parameter
        // (source_parameter_id matches docParam, or is null/same as docParam)
        $src = $e->source_parameter_id ?? null;
        if ($src !== null && $docParam && (int) $src !== $docParam->id) {
            return '—'; // element references a different parameter — don't override
        }
        return $this->fmt($context['od_dim_min']) . '–' . $this->fmt($context['od_dim_max']);
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
