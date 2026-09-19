<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Services\PartVariantGrouping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class RetireLegacyPartGroups extends Command
{
    protected $signature = 'part-groups:retire-legacy {--apply : Apply the audited plan; otherwise read-only}';
    protected $description = 'Move legacy print groups to IPL families / Part Groups, preserving an audit backup';

    public function handle(): int
    {
        if (! Schema::hasColumn('components', 'kit_prl_choice_group')) {
            $this->info('Legacy column already removed. Nothing to migrate.');
            return self::SUCCESS;
        }
        $parts = Component::withTrashed()->whereNotNull('kit_prl_choice_group')
            ->where('kit_prl_choice_group', '<>', '')->get();
        if ($parts->isEmpty()) {
            $this->info('No legacy groups remain.');
            return self::SUCCESS;
        }
        $groups = ManualPartGroup::with('options')->get();
        $plans = [];
        $errors = [];
        $auto = [];
        // Retired Parts are archived and cleared too, but must not become new
        // active group options. Their full original records remain in the backup.
        foreach ($parts->filter(fn ($part) => ! $part->trashed())->groupBy(fn ($part) => $part->manual_id.'|'.$part->kit_prl_choice_group) as $legacy => $members) {
            // An explicitly configured initial-bushing identity is authoritative,
            // even where the old print group contained only some repair sizes.
            foreach ($members->where('is_bush', true)->groupBy('bush_ipl_num') as $initial => $bushes) {
                if (trim((string) $initial) === '') {
                    $errors[] = $legacy.': bushing has no Initial Bushing IPL';
                    continue;
                }
                $complete = Component::where('manual_id', $members->first()->manual_id)
                    ->where('is_bush', true)->where('bush_ipl_num', $initial)->get();
                $plans['bush|'.$members->first()->manual_id.'|'.$initial] = [
                    'type' => ManualPartGroup::TYPE_OVERSIZE, 'name' => 'Bushing '.$initial,
                    'members' => $complete, 'initial' => (string) $initial,
                ];
            }
            $ordinary = $members->where('is_bush', false);
            // Different named components are not alternatives (e.g. bolt / fitting).
            foreach ($ordinary->groupBy(fn ($part) => strtoupper(trim((string) $part->name))) as $name => $variants) {
                $families = $variants->map(fn ($part) => PartVariantGrouping::iplFamily($part->ipl_num))->unique();
                if ($families->count() === 1) {
                    $auto[] = $members->first()->manual_id.': '.$variants->pluck('ipl_num')->implode(', ');
                    continue;
                }
                $plans['alt|'.$legacy.'|'.$name] = [
                    'type' => ManualPartGroup::TYPE_ALTERNATIVE,
                    'name' => $variants->first()->name.' '.$families->implode(' / '),
                    'members' => $variants, 'initial' => null,
                ];
            }
        }
        foreach ($plans as &$plan) {
            $ids = $plan['members']->pluck('id')->sort()->values()->all();
            $manualId = (int) $plan['members']->first()->manual_id;
            $matches = $groups->where('manual_id', $manualId)->filter(fn ($g) =>
                in_array($g->type, [ManualPartGroup::TYPE_ALTERNATIVE, ManualPartGroup::TYPE_OVERSIZE], true)
                && $g->options->pluck('component_id')->intersect($ids)->isNotEmpty());
            $exact = $matches->filter(fn ($g) => $g->type === $plan['type']
                && $g->options->pluck('component_id')->sort()->values()->all() === $ids);
            if ($matches->isNotEmpty() && ($exact->count() !== 1 || $matches->count() !== 1)) {
                $errors[] = $manualId.': '.$plan['name'].' conflicts with existing Part Groups';
            }
            if (count($ids) < 2) {
                $errors[] = $manualId.': '.$plan['name'].' has fewer than two members';
            }
            $plan['existing_id'] = $exact->first()?->id;
        }
        unset($plan);
        $this->table(['Manual', 'Type', 'Members', 'Action'], collect($plans)->map(fn ($p) => [
            $p['members']->first()->manual_id, $p['type'], $p['members']->pluck('ipl_num')->implode(', '),
            $p['existing_id'] ? 'reuse #'.$p['existing_id'] : 'create',
        ])->all());
        $this->line('Automatic IPL families: '.count($auto).'; legacy parts: '.$parts->count());
        $this->line('Archived parts to clear without creating groups: '.$parts->filter(fn ($part) => $part->trashed())->count());
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors !== []) {
            $this->error('Nothing applied. Resolve the reported conflicts first.');
            return self::FAILURE;
        }
        if (! $this->option('apply')) {
            $this->info('Dry run only. No database changes.');
            return self::SUCCESS;
        }
        $backup = 'part-groups/legacy-retirement-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
        $audit = ['parts' => $parts->map->getAttributes()->all(), 'automatic_families' => $auto,
            'plans' => collect($plans)->map(fn ($p) => [
                'type' => $p['type'], 'name' => $p['name'], 'existing_id' => $p['existing_id'],
                'component_ids' => $p['members']->pluck('id')->all(),
            ])->values()->all()];
        if (! Storage::disk('local')->put($backup, json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $this->error('Could not write the audit backup. Nothing applied.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($plans, $parts, $backup): void {
            foreach ($plans as $plan) {
                if ($plan['existing_id']) {
                    continue;
                }
                $members = $plan['members']->sort(fn ($a, $b) => strnatcasecmp($a->ipl_num, $b->ipl_num))->values();
                $group = ManualPartGroup::create([
                    'manual_id' => $members->first()->manual_id,
                    'code' => 'MIG-'.substr(hash('sha256', $plan['type'].'|'.$members->pluck('id')->implode(',')), 0, 20),
                    'name' => $plan['name'], 'type' => $plan['type'],
                    'behavior' => ManualPartGroup::behaviorForType($plan['type']),
                    'applies_to' => ManualPartGroup::validScopes(),
                    'notes' => 'Migrated from retired grouping. Audit: '.$backup,
                ]);
                $default = $plan['initial'] ? $members->firstWhere('ipl_num', $plan['initial']) : null;
                $default = $default ?? $members->first();
                foreach ($members as $index => $part) {
                    $option = $group->options()->create([
                        'component_id' => $part->id, 'part_number' => $part->part_number,
                        'ipl_num' => $part->ipl_num, 'label' => $part->name,
                        'is_default' => $part->id === $default->id, 'sort_order' => $index,
                        'option_kind' => $plan['type'] === ManualPartGroup::TYPE_OVERSIZE
                            ? ($part->id === $default->id ? 'original' : 'oversize') : 'alternate',
                    ]);
                    $option->coverages()->create(['component_id' => $part->id, 'qty' => max(1, (int) $part->units_assy)]);
                }
            }
            // Compare old values so concurrent edits cannot be silently cleared.
            foreach ($parts as $part) {
                $updated = Component::withTrashed()->whereKey($part->id)->where('kit_prl_choice_group', $part->kit_prl_choice_group)
                    ->update(['kit_prl_choice_group' => null]);
                if ($updated !== 1) {
                    throw new \RuntimeException('Legacy source changed during migration; transaction rolled back.');
                }
            }
            activity('part_groups')->withProperties(['backup' => $backup, 'components' => $parts->pluck('id')->all()])
                ->log('Legacy grouping retired');
        });
        $this->info('Applied. Audit backup: storage/app/'.$backup);
        return self::SUCCESS;
    }
}
