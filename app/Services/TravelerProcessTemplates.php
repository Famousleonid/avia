<?php

namespace App\Services;

use App\Models\{Manual, ManualProcess, Process, ProcessName, Tdr, TdrProcess, TravelerProcessTemplate, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TravelerProcessTemplates
{
    // Explicit, user-approved exceptions only; never infer families by stripping a dash.
    private static function partNumberFamilies(?int $manualId): array
    {
        $number = $manualId ? Manual::whereKey($manualId)->value('number') : null;
        $rules = json_decode(file_get_contents(config_path('traveler_part_families.json')), true, 512, JSON_THROW_ON_ERROR);
        return $rules[$number ?? ''] ?? [];
    }

    public static function normalizePartNumbers(int $manualId, string $value): string
    {
        $numbers = array_values(array_unique(array_filter(array_map('trim', explode(';', $value)), fn ($number) => $number !== '')));
        // Exact future Parts are allowed. Only explicitly approved tokens may contain placeholders.
        $families = self::partNumberFamilies($manualId);
        $missing = array_filter($numbers, fn ($number) => !isset($families[$number])
            && !preg_match('/^[A-Za-z0-9][A-Za-z0-9.\/ -]*[A-Za-z0-9]$|^[A-Za-z0-9]$/D', $number));
        if (!$numbers || $missing) {
            throw ValidationException::withMessages(['part_number' => $missing
                ? 'Invalid part numbers or unapproved family: '.implode('; ', $missing)
                : 'Enter at least one part number.']);
        }
        return implode(';', $numbers);
    }

    // Same per-manual access as ManualController::ensureManualAccess. No new permission.
    public static function canManage(User $user, Manual $manual): bool
    {
        return $user->roleIs('Admin') || $user->hasFullManualsAccess()
            || $manual->permittedUsers()->where('users.id', $user->id)->exists();
    }

    public static function forTdr(Tdr $tdr)
    {
        $manualId = $tdr->component?->manual_id;
        $partNumber = trim((string) $tdr->component?->part_number);
        $families = self::partNumberFamilies($manualId);
        // A family marker is not itself a complete component P/N.
        $tokens = isset($families[$partNumber]) ? [] : [$partNumber];
        foreach ($families as $token => $rule) {
            if (!isset($families[$partNumber]) && !in_array($partNumber, $rule['exclude'], true)
                && collect($rule['prefixes'])->contains(fn ($prefix) => str_starts_with($partNumber, $prefix) && strlen($partNumber) > strlen($prefix))) {
                $tokens[] = $token;
            }
        }
        return TravelerProcessTemplate::query()
            ->where('manual_id', $manualId)
            ->where(function ($query) use ($tokens) {
                if ($tokens === []) {
                    $query->whereRaw('1 = 0');
                }
                foreach ($tokens as $token) {
                    // INSTR is literal: underscores in the approved token are not SQL wildcards.
                    $query->orWhereRaw("INSTR(CONCAT(';', part_number, ';'), ?) > 0", [';'.$token.';']);
                }
            })
            ->whereHas('steps')
            ->where('active', true);
    }

    public static function assertUnique(int $manualId, string $numbers, ?int $exceptId = null): void
    {
        $families = self::partNumberFamilies($manualId);
        $expand = fn ($token) => $families[$token] ?? ['exact' => $token];
        $matches = fn ($rule, $number) => isset($rule['exact']) ? $rule['exact'] === $number
            : (!in_array($number, $rule['exclude'], true) && collect($rule['prefixes'])
                ->contains(fn ($prefix) => str_starts_with($number, $prefix) && strlen($number) > strlen($prefix)));
        foreach (TravelerProcessTemplate::where('manual_id', $manualId)->where('active', true)
            ->when($exceptId, fn ($q) => $q->where('id', '<>', $exceptId))->lockForUpdate()->get() as $other) {
            foreach (explode(';', $numbers) as $left) foreach (explode(';', $other->part_number) as $right) {
                $a = $expand(trim($left)); $b = $expand(trim($right));
                $overlap = isset($a['exact']) ? $matches($b, $a['exact'])
                    : (isset($b['exact']) ? $matches($a, $b['exact'])
                        : collect($a['prefixes'])->contains(fn ($x) => collect($b['prefixes'])
                            ->contains(fn ($y) => str_starts_with($x, $y) || str_starts_with($y, $x))));
                if ($overlap) throw ValidationException::withMessages(['part_number' => 'An active template already covers this part number: '.$other->name]);
            }
        }
    }

    public static function additionalDescriptions(array $steps): array
    {
        $processes = Process::with('process_name')->whereIn('id', array_column($steps, 'process_id'))->get()->keyBy('id');
        foreach ($steps as &$step) {
            $process = $processes->get($step['process_id']);
            if ($process) {
                $step['description'] = TravelerPrintDescription::additional(
                    $step['description'] ?? null, $process->process,
                    [$process->process_name?->name ?? '']
                );
            }
        }
        return $steps;
    }

    public static function validateDescriptions(int $manualId, array $steps): void
    {
        $requirements = ManualProcess::where('manual_id', $manualId)
            ->whereIn('processes_id', array_column($steps, 'process_id'))->with('process')->get()->keyBy('processes_id');
        $errors = [];
        foreach (array_values($steps) as $index => $step) {
            $rule = $requirements->get($step['process_id']);
            foreach (['fig' => 'FIG', 'zone' => 'ZONE'] as $field => $label) {
                $instructions = ($rule?->process?->process ?? '')."\n".($step['description'] ?? '');
                if ($rule?->{'requires_'.$field} && stripos($instructions, $label) === false) {
                    $errors['steps.'.$index.'.description'][] = 'Step '.($index + 1).': Description requires '.$label.'.';
                }
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function applicationHasProcesses(object $application): bool
    {
        $ids = json_decode($application->process_ids, true);
        // Fail closed for an invalid legacy snapshot, not for a deliberately cleared TDR.
        if (!is_array($ids) || $ids === []) return true;
        return TdrProcess::where('tdrs_id', $application->tdr_id)->whereIn('id', $ids)->exists();
    }

    public function apply(Tdr $tdr, int $templateId, int $revision, User $user): array
    {
        return DB::transaction(function () use ($tdr, $templateId, $revision, $user) {
            $tdr = Tdr::query()->lockForUpdate()->findOrFail($tdr->id);
            abort_if(self::forTdr($tdr)->count() > 1, 409, 'Multiple templates cover this part. Resolve the conflict in the manual before applying.');
            $template = self::forTdr($tdr)->lockForUpdate()->findOrFail($templateId);
            if ($template->revision !== $revision) {
                abort(409, 'The template changed. Reload and review it before applying.');
            }
            $previousApplication = DB::table('tdr_traveler_template_applications')
                ->where('tdr_id', $tdr->id)->where('template_id', $templateId)->lockForUpdate()->first();
            if ($previousApplication && self::applicationHasProcesses($previousApplication)) {
                abort(409, 'This template has already been applied to this part.');
            }
            $steps = $template->steps()->with('process.process_name')->get();
            $catalog = ManualProcess::where('manual_id', $template->manual_id)
                ->whereIn('processes_id', $steps->pluck('process_id'))->get()->keyBy('processes_id');
            if ($steps->isEmpty() || $steps->contains(fn ($step) => ! $step->process?->process_name || ! $catalog->has($step->process_id))) {
                throw ValidationException::withMessages(['template_id' => 'A template process is no longer available in this manual. Please update the template.']);
            }
            self::validateDescriptions($template->manual_id, $steps->toArray());
            $additional = collect(self::additionalDescriptions($steps->toArray()))->keyBy('id');
            foreach ($steps as $step) $step->description = $additional[$step->id]['description'];
            $existing = TdrProcess::where('tdrs_id', $tdr->id)->lockForUpdate()->get();
            $sort = (int) $existing->max('sort_order');
            // Combine the template's marked external steps into one new Traveler.
            // Existing WO groups and unmarked standalone steps remain untouched.
            $templateGroup = (int) $existing->where('in_traveler', true)
                ->map(fn ($row) => $row->traveler_group ?: 1)->max() + 1;
            $ids = [];
            $machiningEcSpecs = [];
            foreach ($steps as $step) {
                $manualProcess = $catalog[$step->process_id];
                $identity = $step->process->process_name->identityName();
                $isEc = $identity === 'EC';
                $isMachiningEc = $identity === 'Machining (EC)';
                $row = TdrProcess::create([
                    'tdrs_id' => $tdr->id,
                    'process_names_id' => $step->process->process_names_id,
                    'processes' => [$step->process_id],
                    'description' => $step->description,
                    'requires_fig' => $manualProcess->requires_fig,
                    'requires_zone' => $manualProcess->requires_zone,
                    'sort_order' => ++$sort,
                    'in_traveler' => (bool) $step->traveler_group,
                    'traveler_group' => $step->traveler_group ? $templateGroup : null,
                    'user_id' => $user->id,
                    'ec' => $isEc || $isMachiningEc,
                    'standalone_ec_only' => $isEc,
                ]);
                $ids[] = $row->id;
                if ($isMachiningEc) {
                    $machiningEcSpecs[] = $step->process_id;
                }
            }
            if ($machiningEcSpecs !== []) {
                $ecSteps = $steps->filter(fn ($step) => in_array($step->process_id, $machiningEcSpecs, true));
                $ec = ProcessName::whereIdentityName('EC')->first()
                    ?? ProcessName::create(['name' => 'EC', 'process_sheet_name' => 'EC', 'form_number' => 'EC', 'sequence_exempt' => true]);
                // The normal Machining (EC) workflow requires a companion EC row.
                // Keep it separate: merging would modify an existing WO/RO process.
                $companion = TdrProcess::create([
                    'tdrs_id' => $tdr->id, 'process_names_id' => $ec->id,
                    'processes' => array_values(array_unique($machiningEcSpecs)),
                    'sort_order' => ++$sort, 'ec' => false, 'standalone_ec_only' => false,
                    'in_traveler' => false, 'user_id' => $user->id,
                    'description' => $ecSteps->pluck('description')->filter()->unique()->implode("\n") ?: null,
                    'requires_fig' => $ecSteps->contains(fn ($step) => $catalog[$step->process_id]->requires_fig),
                    'requires_zone' => $ecSteps->contains(fn ($step) => $catalog[$step->process_id]->requires_zone),
                ]);
                $ids[] = $companion->id;
            }
            if ($previousApplication) {
                // Preserve the old snapshot in the audit before replacing the current application.
                activity('tdr_traveler')->performedOn($tdr)->causedBy($user)
                    ->event('template_reapplied')->withProperties(['previous_application' => (array) $previousApplication])
                    ->log('Traveler template reapplied after all previous process rows were deleted');
            }
            DB::table('tdr_traveler_template_applications')->updateOrInsert([
                'tdr_id' => $tdr->id, 'template_id' => $template->id,
            ], [
                'tdr_id' => $tdr->id, 'template_id' => $template->id, 'revision' => $template->revision,
                'user_id' => $user->id, 'snapshot' => $template->load('steps.process')->toJson(),
                'process_ids' => json_encode($ids), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $ids;
        });
    }
}
