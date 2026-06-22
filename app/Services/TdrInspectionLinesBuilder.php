<?php

namespace App\Services;

use App\Models\Code;
use App\Models\Condition;
use App\Models\Necessary;
use App\Models\Workorder;
use App\Models\WorkorderUnitInspection;
use Illuminate\Support\Facades\Log;

/**
 * Builds the ordered list of TDR Form inspection lines for a workorder.
 *
 * Single source of truth shared by the renderer (TdrPrintFormController::tdrForm)
 * and the row counter (TdrController::countTdrFormRows) so the printed form and
 * its reserved row count can never drift apart — the count is simply
 * count(build()).
 *
 * Order of lines: unit inspections → null-component conditions (+ Missing) →
 * Order New component pairs grouped by REASON → "is necessary" component rows.
 *
 * NB: for Order New rows the reason is taken from the CODE (the field edited in
 * the "Ordered Parts" modal), not from conditions. "Customer Request" is not a
 * scrap reason, so its line omits the "(scrap)" label.
 */
class TdrInspectionLinesBuilder
{
    /**
     * @return array<int, string>
     */
    public function build(Workorder $current_wo): array
    {
        $current_wo->loadMissing('tdrs.component', 'tdrs.conditions', 'tdrs.necessaries', 'tdrs.codes');

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::missing();

        $nullComponentConditions = []; // component_id == null
        $groupedByConditions = [];     // component_id !== null, necessaries_id == Order New
        $necessaryComponents = [];     // component_id !== null, necessaries_id != Order New
        $hasMissingComponents = false;

        $missingConditionName = Condition::NAME_PARTS_MISSING;
        $unitInspections = WorkorderUnitInspection::query()
            ->with('condition:id,name')
            ->where('workorder_id', $current_wo->id)
            ->where(function ($query) {
                $query->where('use_tdr', true)
                    ->orWhereNull('use_tdr');
            })
            ->orderBy('id')
            ->get();

        $unitInspectionLines = $unitInspections
            ->map(function (WorkorderUnitInspection $inspection) use ($missingConditionName) {
                $conditionName = trim((string) ($inspection->condition->name ?? ''));
                $notes = trim((string) ($inspection->notes ?? ''));

                if (strcasecmp($conditionName, $missingConditionName) === 0) {
                    return null;
                }

                if ($conditionName !== '' && preg_match('/^note\s+\d+$/i', $conditionName)) {
                    return $notes !== '' ? $notes : null;
                }

                if ($conditionName === '') {
                    return $notes !== '' ? $notes : null;
                }

                return $notes !== '' ? $conditionName . ' (' . $notes . ')' : $conditionName;
            })
            ->filter()
            ->values()
            ->all();

        $unitInspectionSourceTdrIds = $unitInspections
            ->pluck('source_tdr_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($current_wo->tdrs as $tdr) {
            // Missing-coded rows are summarised by a single line further down.
            if ($code && $tdr->codes_id == $code->id) {
                $hasMissingComponents = true;
                continue;
            }

            if ($tdr->component_id === null) {
                if (in_array((int) $tdr->id, $unitInspectionSourceTdrIds, true)) {
                    continue;
                }

                $conditions = $tdr->conditions;
                if ($conditions) {
                    $description = trim((string) $tdr->description);
                    $isNoteCondition = preg_match('/^note\s+\d+$/i', $conditions->name);

                    if ($isNoteCondition) {
                        // "note 1/2/…" carry only their description (skip when empty).
                        if ($description !== '') {
                            $nullComponentConditions[] = $description;
                        }
                    } else {
                        $conditionString = $conditions->name;
                        if ($description !== '') {
                            $conditionString .= ' ' . $description;
                        }
                        $nullComponentConditions[] = $conditionString;
                    }
                } else {
                    Log::warning('TDR has null component_id but no conditions relation', [
                        'tdr_id' => $tdr->id,
                    ]);
                }
            } elseif ($necessary && $tdr->component_id !== null && $tdr->necessaries_id == $necessary->id) {
                // Order New: group by REASON (from the code). Customer Request is not
                // a scrap reason, so its line omits "(scrap)".
                $component = $tdr->component;
                $reason = $tdr->codes;
                if ($component && $reason) {
                    $reasonName = $reason->name;
                    $reasonPrefix = strcasecmp((string) $reasonName, 'Customer Request') === 0
                        ? $reasonName . ': '
                        : $reasonName . ' (scrap): ';

                    if (! empty($tdr->description)) {
                        $componentString = sprintf(
                            "(%s%s)<b> %s </b>: ( %s)",
                            strtoupper($component->ipl_num),
                            $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs',
                            strtoupper($component->name),
                            strtoupper($tdr->description),
                        );
                    } else {
                        $componentString = sprintf(
                            "(%s%s)<b> %s </b> ",
                            strtoupper($component->ipl_num),
                            $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs',
                            strtoupper($component->name),
                        );
                    }

                    if (! isset($groupedByConditions[$reasonName])) {
                        $groupedByConditions[$reasonName] = [];
                    }

                    $lastKey = count($groupedByConditions[$reasonName]) - 1;
                    $lastString = $lastKey >= 0 ? $groupedByConditions[$reasonName][$lastKey] : '';

                    if (strlen($lastString . ', ' . $componentString) <= 120) {
                        if ($lastKey >= 0) {
                            $groupedByConditions[$reasonName][$lastKey] .= ', ' . $componentString;
                        } else {
                            $groupedByConditions[$reasonName][] = $reasonPrefix . $componentString;
                        }
                    } else {
                        $groupedByConditions[$reasonName][] = $reasonPrefix . $componentString;
                    }
                }
            } elseif ($necessary && $tdr->component_id !== null && $tdr->necessaries_id !== $necessary->id) {
                $component = $tdr->component;
                $necessaries = $tdr->necessaries;
                $codes = $tdr->codes;
                $description = $tdr->description;
                if ($component && $necessaries && $codes) {
                    $componentName = trim((string) $component->name);
                    $descriptionText = trim((string) $description);
                    $showDescription = $descriptionText !== ''
                        && strcasecmp($descriptionText, $componentName) !== 0;

                    if ($showDescription) {
                        $necessaryComponents[] = sprintf(
                            "(%s) <b>%s</b> IS NECESSARY: %s - %s ( %s )",
                            strtoupper($component->ipl_num),
                            strtoupper($component->name),
                            strtoupper($necessaries->name),
                            strtoupper($codes->name),
                            strtoupper($description),
                        );
                    } else {
                        $necessaryComponents[] = sprintf(
                            "(%s) <b>%s</b> IS NECESSARY: %s - %s ",
                            strtoupper($component->ipl_num),
                            strtoupper($component->name),
                            strtoupper($necessaries->name),
                            strtoupper($codes->name),
                        );
                    }
                }
            }
        }

        if ($hasMissingComponents) {
            $missingCondition = Condition::where('name', $missingConditionName)->first();
            if ($missingCondition) {
                $nullComponentConditions[] = $missingCondition->name;
            }
        }

        // Assemble in print order.
        $tdrInspections = $unitInspectionLines;

        if (! empty($nullComponentConditions)) {
            $tdrInspections = array_merge($tdrInspections, $nullComponentConditions);
        }

        foreach ($groupedByConditions as $components) {
            foreach ($components as $componentLine) {
                $tdrInspections[] = $componentLine;
            }
        }

        if (! empty($necessaryComponents)) {
            $tdrInspections = array_merge($tdrInspections, $necessaryComponents);
        }

        return $tdrInspections;
    }
}
