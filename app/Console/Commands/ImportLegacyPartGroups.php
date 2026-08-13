<?php

namespace App\Console\Commands;

use App\Models\ComponentAssembly;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyPartGroups extends Command
{
    protected $signature = 'parts:import-legacy-groups {--manual= : Limit to one manual ID} {--apply : Create groups}';

    protected $description = 'Audit legacy component_assemblies and optionally import them into ASSY groups.';

    public function handle(): int
    {
        $query = ComponentAssembly::query()
            ->with('component:id,manual_id,part_number,ipl_num')
            ->withIdentifier()
            ->orderBy('id');

        if ((int) $this->option('manual') > 0) {
            $manualId = (int) $this->option('manual');
            $query->whereHas('component', fn ($component) => $component->where('manual_id', $manualId));
        }

        $groups = $query->get()
            ->filter(fn (ComponentAssembly $assembly): bool => $assembly->component !== null)
            ->groupBy(fn (ComponentAssembly $assembly): string => implode('|', [
                (int) $assembly->component->manual_id,
                $this->normalize($assembly->assy_part_number),
                $this->normalize($assembly->assy_ipl_num),
            ]));

        $rows = $groups->map(function ($members, string $key): array {
            /** @var ComponentAssembly $first */
            $first = $members->first();

            return [
                'manual_id' => (int) $first->component->manual_id,
                'assy_part_number' => trim((string) $first->assy_part_number),
                'assy_ipl_num' => trim((string) $first->assy_ipl_num),
                'members' => $members->count(),
                'invalid_qty' => $members->filter(fn (ComponentAssembly $assembly): bool => $this->quantity($assembly->units_assy) === null)->count(),
                'key' => $key,
            ];
        })->values();

        $this->table(
            ['Manual ID', 'ASSY P/N', 'ASSY IPL', 'Members', 'Invalid qty'],
            $rows->map(fn (array $row): array => array_values(array_diff_key($row, ['key' => true])))->all()
        );

        if (! $this->option('apply')) {
            $this->info("Dry audit complete: {$rows->count()} candidate groups. Run with --apply to create them.");

            return self::SUCCESS;
        }

        $created = 0;
        $linked = 0;
        foreach ($groups as $key => $members) {
            DB::transaction(function () use ($key, $members, &$created, &$linked): void {
                /** @var ComponentAssembly $first */
                $first = $members->first();
                $code = 'LEGACY-ASSY-'.strtoupper(substr(sha1($key), 0, 12));
                $group = ManualPartGroup::withTrashed()->firstOrNew(['code' => $code]);
                if (! $group->exists) {
                    $group->fill([
                        'manual_id' => $first->component->manual_id,
                        'name' => trim('Legacy ASSY '.($first->assy_part_number ?: $first->assy_ipl_num)),
                        'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
                        'type' => ManualPartGroup::TYPE_ASSY,
                        'applies_to' => ManualPartGroup::validScopes(),
                        'notes' => 'Imported from component_assemblies; review composition before use.',
                    ])->save();
                    $created++;
                } elseif ($group->trashed()) {
                    return;
                }

                $option = $group->options()->firstOrCreate(
                    ['part_number' => trim((string) $first->assy_part_number), 'ipl_num' => trim((string) $first->assy_ipl_num) ?: null],
                    ['option_kind' => 'assy', 'is_default' => true, 'sort_order' => 0]
                );

                foreach ($members as $member) {
                    $qty = $this->quantity($member->units_assy) ?? 1;
                    $coverage = $option->coverages()->updateOrCreate(
                        ['component_id' => $member->component_id],
                        [
                            'legacy_component_assembly_id' => $member->id,
                            'qty' => $qty,
                            'applies_to' => ManualPartGroup::validScopes(),
                        ]
                    );
                    if ($coverage->wasRecentlyCreated) {
                        $linked++;
                    }
                }
            });
        }

        $this->info("Created {$created} groups and linked {$linked} legacy assembly rows.");

        return self::SUCCESS;
    }

    private function normalize(?string $value): string
    {
        return (string) preg_replace('/\s+/', '', mb_strtoupper(trim((string) $value)));
    }

    private function quantity(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || ! ctype_digit($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }
}
