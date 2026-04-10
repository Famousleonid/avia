<?php

namespace App\Console\Commands;

use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\Instruction;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Role;
use App\Models\Scope;
use App\Models\Task;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SeedLoadWorkorders extends Command
{
    protected $signature = 'dev:seed-load-workorders
        {--count=3000 : Number of workorders to generate}
        {--users=50 : Number of additional users to create}
        {--manuals=120 : Number of manuals to create}
        {--units-per-manual=3 : Units per generated manual}
        {--components-per-manual=8 : Components per generated manual}
        {--prefix=LOADTEST : Marker prefix for generated data}
        {--cleanup : Delete previously generated data for the prefix instead of creating}';

    protected $description = 'Seed a large connected workorder dataset for load testing without touching reference tables.';

    private array $columnCache = [];

    public function handle(): int
    {
        DB::disableQueryLog();

        $prefix = strtoupper(trim((string) $this->option('prefix')));
        if ($prefix === '') {
            $prefix = 'LOADTEST';
        }

        if ($this->option('cleanup')) {
            return $this->cleanupDataset($prefix);
        }

        $count = max(1, (int) $this->option('count'));
        $extraUsers = max(0, (int) $this->option('users'));
        $manualCount = max(1, (int) $this->option('manuals'));
        $unitsPerManual = max(1, (int) $this->option('units-per-manual'));
        $componentsPerManual = max(1, (int) $this->option('components-per-manual'));

        $refs = $this->loadReferenceData();
        if ($refs === null) {
            return self::FAILURE;
        }

        $this->info("Prefix: {$prefix}");
        $this->info("Generating {$count} workorders, {$extraUsers} users, {$manualCount} manuals");

        $createdUsers = $this->createUsers($prefix, $extraUsers, $refs);
        $manualGraph = $this->createManualGraph($prefix, $manualCount, $unitsPerManual, $componentsPerManual, $refs);
        $processPool = $this->ensureProcessPool($prefix, $refs['process_name_ids']);
        $this->createManualProcesses($manualGraph['manuals'], $processPool);

        $workorderSummary = $this->createWorkorderGraph($prefix, $count, $refs, $manualGraph, $processPool);

        $this->line('');
        $this->info('Load dataset created.');
        $this->table(
            ['Entity', 'Created'],
            [
                ['users', $createdUsers],
                ['manuals', $manualGraph['manuals']->count()],
                ['units', $manualGraph['units']->count()],
                ['components', $manualGraph['components']->count()],
                ['workorders', $workorderSummary['workorders']],
                ['mains', $workorderSummary['mains']],
                ['general task statuses', $workorderSummary['statuses']],
                ['tdrs', $workorderSummary['tdrs']],
                ['tdr processes', $workorderSummary['tdrProcesses']],
            ]
        );

        $this->comment("Cleanup later: php artisan dev:seed-load-workorders --cleanup --prefix={$prefix}");

        return self::SUCCESS;
    }

    private function loadReferenceData(): ?array
    {
        $customers = Customer::query()->pluck('id')->all();
        $instructions = Instruction::query()->pluck('id')->all();
        $processNameIds = ProcessName::query()->pluck('id', 'name');
        $generalTasks = GeneralTask::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name']);
        $tasks = Task::query()->orderBy('general_task_id')->orderBy('id')->get(['id', 'name', 'general_task_id', 'task_has_start_date']);

        if (empty($customers) || empty($instructions) || $generalTasks->isEmpty() || $tasks->isEmpty() || $processNameIds->isEmpty()) {
            $this->error('Missing required reference data. Customers, instructions, general tasks, tasks and process names must already exist.');
            return null;
        }

        $groupedTasks = $tasks->groupBy('general_task_id')->map(function (Collection $items) {
            return $items->values();
        });

        return [
            'customer_ids' => $customers,
            'instruction_ids' => $instructions,
            'code_ids' => Code::query()->pluck('id')->all(),
            'condition_ids' => Condition::query()->pluck('id')->all(),
            'necessary_ids' => Necessary::query()->pluck('id')->all(),
            'plane_ids' => Plane::query()->pluck('id')->all(),
            'builder_ids' => Builder::query()->pluck('id')->all(),
            'scope_ids' => Scope::query()->pluck('id')->all(),
            'team_ids' => Team::query()->pluck('id')->all(),
            'role_ids' => Role::query()->where('name', '!=', 'Admin')->pluck('id')->all(),
            'all_role_ids' => Role::query()->pluck('id')->all(),
            'user_rows' => User::query()->select('id', 'name')->get(),
            'general_tasks' => $generalTasks,
            'tasks_by_general' => $groupedTasks,
            'approved_task' => $tasks->firstWhere('name', 'Approved'),
            'completed_task' => $tasks->firstWhere('name', 'Completed'),
            'process_name_ids' => $processNameIds,
        ];
    }

    private function createUsers(string $prefix, int $extraUsers, array &$refs): int
    {
        if ($extraUsers <= 0) {
            return 0;
        }

        $now = now();
        $roleIds = !empty($refs['role_ids']) ? $refs['role_ids'] : $refs['all_role_ids'];
        $teamIds = $refs['team_ids'];

        $records = [];
        for ($i = 1; $i <= $extraUsers; $i++) {
            $seq = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $records[] = [
                'name' => "{$prefix} User {$seq}",
                'email' => strtolower("{$prefix}.user.{$seq}." . now()->format('YmdHis') . '@example.test'),
                'email_verified_at' => $now,
                'password' => Hash::make('password'),
                'role_id' => !empty($roleIds) ? $roleIds[array_rand($roleIds)] : null,
                'team_id' => !empty($teamIds) ? $teamIds[array_rand($teamIds)] : null,
                'is_admin' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 200) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $newUsers = User::query()
            ->where('email', 'like', strtolower("{$prefix}.user.%@example.test"))
            ->select('id', 'name')
            ->get();

        $refs['user_rows'] = $refs['user_rows']->concat($newUsers)->values();

        return count($records);
    }

    private function createManualGraph(string $prefix, int $manualCount, int $unitsPerManual, int $componentsPerManual, array $refs): array
    {
        $now = now();
        $manuals = [];
        for ($i = 1; $i <= $manualCount; $i++) {
            $seq = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $manuals[] = [
                'number' => "{$prefix}-MAN-{$seq}",
                'title' => "{$prefix} Manual {$seq}",
                'lib' => (string) rand(120, 990),
                'revision_date' => Carbon::now()->subDays(rand(0, 900))->toDateString(),
                'unit_name' => "{$prefix} Unit Family {$seq}",
                'unit_name_training' => "{$prefix} Training {$seq}",
                'training_hours' => (string) rand(2, 24),
                'planes_id' => $this->pickOptionalId($refs['plane_ids']),
                'builders_id' => $this->pickOptionalId($refs['builder_ids']),
                'scopes_id' => $this->pickOptionalId($refs['scope_ids']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($manuals, 200) as $chunk) {
            DB::table('manuals')->insert($chunk);
        }

        $manualRows = Manual::query()
            ->where('number', 'like', "{$prefix}-MAN-%")
            ->get(['id', 'number']);

        $unitRecords = [];
        $componentRecords = [];
        foreach ($manualRows as $manualIndex => $manual) {
            for ($u = 1; $u <= $unitsPerManual; $u++) {
                $unitRecord = [
                    'part_number' => "{$prefix}-UNIT-" . str_pad((string) ($manualIndex + 1), 4, '0', STR_PAD_LEFT) . '-' . $u,
                    'verified' => true,
                    'manual_id' => $manual->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($this->hasColumn('units', 'name')) {
                    $unitRecord['name'] = "{$prefix} Unit {$manualIndex}-{$u}";
                }

                if ($this->hasColumn('units', 'description')) {
                    $unitRecord['description'] = "[LOADTEST:{$prefix}] Unit {$manualIndex}-{$u}";
                }

                if ($this->hasColumn('units', 'eff_code')) {
                    $unitRecord['eff_code'] = 'ALL';
                }

                $unitRecords[] = $unitRecord;
            }

            for ($c = 1; $c <= $componentsPerManual; $c++) {
                $componentRecord = [
                    'part_number' => "{$prefix}-COMP-" . str_pad((string) ($manualIndex + 1), 4, '0', STR_PAD_LEFT) . '-' . $c,
                    'assy_part_number' => "{$prefix}-ASSY-" . str_pad((string) ($manualIndex + 1), 4, '0', STR_PAD_LEFT),
                    'name' => "{$prefix} Component {$manualIndex}-{$c}",
                    'ipl_num' => 'IPL-' . rand(1000, 9999),
                    'assy_ipl_num' => 'ASSY-' . rand(100, 999),
                    'manual_id' => $manual->id,
                    'log_card' => (bool) rand(0, 1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($this->hasColumn('components', 'repair')) {
                    $componentRecord['repair'] = (bool) rand(0, 1);
                }

                if ($this->hasColumn('components', 'eff_code')) {
                    $componentRecord['eff_code'] = 'ALL';
                }

                $componentRecords[] = $componentRecord;
            }
        }

        foreach (array_chunk($unitRecords, 500) as $chunk) {
            DB::table('units')->insert($chunk);
        }

        foreach (array_chunk($componentRecords, 500) as $chunk) {
            DB::table('components')->insert($chunk);
        }

        $unitRows = Unit::query()
            ->where('part_number', 'like', "{$prefix}-UNIT-%")
            ->get(['id', 'manual_id', 'part_number']);

        $componentRows = Component::query()
            ->where('part_number', 'like', "{$prefix}-COMP-%")
            ->get(['id', 'manual_id', 'part_number']);

        return [
            'manuals' => $manualRows,
            'units' => $unitRows,
            'components' => $componentRows,
        ];
    }

    private function ensureProcessPool(string $prefix, Collection $processNameIds): Collection
    {
        $existing = Process::query()->get(['id', 'process_names_id', 'process'])->groupBy('process_names_id');
        $toInsert = [];
        $now = now();

        foreach ($processNameIds as $name => $processNameId) {
            $count = count($existing[$processNameId] ?? []);
            for ($i = $count + 1; $i <= max(2, $count); $i++) {
                $toInsert[] = [
                    'process_names_id' => $processNameId,
                    'process' => "[LOADTEST:{$prefix}] {$name} {$i}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('processes')->insert($chunk);
            }
        }

        return Process::query()->get(['id', 'process_names_id', 'process'])->groupBy('process_names_id');
    }

    private function createManualProcesses(Collection $manuals, Collection $processPool): void
    {
        $records = [];
        $now = now();

        foreach ($manuals as $manual) {
            $processIds = $processPool
                ->flatten(1)
                ->random(min(rand(3, 6), $processPool->flatten(1)->count()))
                ->pluck('id')
                ->unique()
                ->values();

            foreach ($processIds as $processId) {
                $records[] = [
                    'manual_id' => $manual->id,
                    'processes_id' => $processId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($records, 1000) as $chunk) {
            DB::table('manual_processes')->insert($chunk);
        }
    }

    private function createWorkorderGraph(string $prefix, int $count, array $refs, array $manualGraph, Collection $processPool): array
    {
        $now = now();
        $unitRows = $manualGraph['units']->values();
        $componentRows = $manualGraph['components']->groupBy('manual_id');
        $users = $refs['user_rows']->values();

        $unitManualMap = $manualGraph['units']->pluck('manual_id', 'id');
        $maxNumber = (int) Workorder::withDrafts()->withTrashed()->max('number');
        $hasDoneAt = $this->hasColumn('workorders', 'done_at');
        $hasDoneUser = $this->hasColumn('workorders', 'done_user_id');

        $workorderRecords = [];
        $meta = [];

        for ($i = 1; $i <= $count; $i++) {
            $number = $maxNumber + $i;
            $marker = "{$prefix}-WO-" . str_pad((string) $i, 6, '0', STR_PAD_LEFT);
            $unit = $unitRows->random();
            $user = $users->random();
            $isDraft = rand(1, 100) <= 10;
            $isApproved = !$isDraft && rand(1, 100) <= 35;
            $isDone = !$isDraft && rand(1, 100) <= 28;
            $openAt = Carbon::now()->subDays(rand(0, 820))->startOfDay();
            $approvedAt = $isApproved ? (clone $openAt)->addDays(rand(1, 45)) : null;
            $doneAt = $isDone ? (clone $openAt)->addDays(rand(5, 70)) : null;

            $record = [
                'number' => $number,
                'approve_at' => $approvedAt,
                'approve_name' => $approvedAt ? $user->name : null,
                'serial_number' => "{$marker}-SN",
                'description' => "[LOADTEST:{$prefix}] Workorder {$i}",
                'amdt' => (string) rand(0, 6),
                'place' => 'TEST-BAY-' . rand(1, 9),
                'open_at' => $openAt,
                'unit_id' => $unit->id,
                'instruction_id' => $refs['instruction_ids'][array_rand($refs['instruction_ids'])],
                'customer_id' => $refs['customer_ids'][array_rand($refs['customer_ids'])],
                'user_id' => $user->id,
                'external_damage' => (bool) rand(0, 1),
                'received_disassembly' => (bool) rand(0, 1),
                'nameplate_missing' => (bool) rand(0, 1),
                'preliminary_test_false' => (bool) rand(0, 1),
                'part_missing' => (bool) rand(0, 1),
                'new_parts' => (bool) rand(0, 1),
                'extra_parts' => (bool) rand(0, 1),
                'disassembly_upon_arrival' => (bool) rand(0, 1),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($this->hasColumn('workorders', 'customer_po')) {
                $record['customer_po'] = $marker;
            }

            if ($this->hasColumn('workorders', 'is_draft')) {
                $record['is_draft'] = $isDraft;
            }

            if ($hasDoneAt) {
                $record['done_at'] = $doneAt;
            }

            if ($hasDoneUser) {
                $record['done_user_id'] = $doneAt ? $user->id : null;
            }

            $workorderRecords[] = $record;
            $meta[$marker] = [
                'open_at' => $openAt,
                'approved_at' => $approvedAt,
                'done_at' => $doneAt,
                'is_draft' => $isDraft,
                'user_id' => $user->id,
                'unit_id' => $unit->id,
                'manual_id' => $unitManualMap[$unit->id] ?? null,
            ];
        }

        foreach (array_chunk($workorderRecords, 400) as $chunk) {
            DB::table('workorders')->insert($chunk);
        }

        $markerColumn = $this->hasColumn('workorders', 'customer_po') ? 'customer_po' : 'description';

        $workorders = DB::table('workorders')
            ->select('id', 'number', DB::raw($markerColumn . ' as marker'))
            ->where($markerColumn, 'like', $markerColumn === 'customer_po' ? "{$prefix}-WO-%" : "[LOADTEST:{$prefix}]%")
            ->orderBy('id')
            ->get();

        [$mainCount, $statusCount] = $this->createMainsAndStatuses($workorders, $meta, $refs);
        [$tdrCount, $tdrProcessCount] = $this->createTdrGraph($workorders, $meta, $refs, $componentRows, $processPool, $prefix);

        return [
            'workorders' => $workorders->count(),
            'mains' => $mainCount,
            'statuses' => $statusCount,
            'tdrs' => $tdrCount,
            'tdrProcesses' => $tdrProcessCount,
        ];
    }

    private function createMainsAndStatuses(Collection $workorders, array $meta, array $refs): array
    {
        $now = now();
        $mainRecords = [];
        $statusRecords = [];

        $groups = $refs['general_tasks']
            ->map(function ($generalTask) use ($refs) {
                return [
                    'id' => $generalTask->id,
                    'tasks' => ($refs['tasks_by_general'][$generalTask->id] ?? collect())->values(),
                ];
            })
            ->filter(fn ($group) => $group['tasks']->isNotEmpty())
            ->values();

        $hasIgnoreRow = $this->hasColumn('mains', 'ignore_row');

        foreach ($workorders as $workorder) {
            $workorderMeta = $meta[$workorder->marker] ?? null;
            if (!$workorderMeta) {
                continue;
            }
            $openAt = $workorderMeta['open_at'];
            $selectedGroups = $groups->shuffle()->take(min(rand(3, 5), $groups->count()));
            $statusByGeneral = [];

            foreach ($selectedGroups as $group) {
                $task = $group['tasks']->first(fn ($item) => !in_array($item->name, ['Approved', 'Completed'], true))
                    ?? $group['tasks']->first();

                if (!$task) {
                    continue;
                }

                $startedAt = (clone $openAt)->addDays(rand(0, 12));
                $finished = rand(1, 100) <= 55;
                $finishedAt = $finished ? (clone $startedAt)->addDays(rand(1, 25)) : null;
                $ignoreRow = !$finished && rand(1, 100) <= 7;

                $mainRecord = [
                    'user_id' => $workorderMeta['user_id'],
                    'workorder_id' => $workorder->id,
                    'task_id' => $task->id,
                    'general_task_id' => $group['id'],
                    'date_start' => $task->task_has_start_date ? $startedAt->toDateString() : null,
                    'date_finish' => $finishedAt?->toDateString(),
                    'description' => "[LOADTEST] {$task->name}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasIgnoreRow) {
                    $mainRecord['ignore_row'] = $ignoreRow;
                }

                $mainRecords[] = $mainRecord;

                $statusByGeneral[$group['id']] = $ignoreRow || $finishedAt !== null;
            }

            if ($refs['approved_task'] && $workorderMeta['approved_at']) {
                $task = $refs['approved_task'];
                $approvedRecord = [
                    'user_id' => $workorderMeta['user_id'],
                    'workorder_id' => $workorder->id,
                    'task_id' => $task->id,
                    'general_task_id' => $task->general_task_id,
                    'date_start' => $task->task_has_start_date ? Carbon::parse($workorderMeta['approved_at'])->subDay()->toDateString() : null,
                    'date_finish' => Carbon::parse($workorderMeta['approved_at'])->toDateString(),
                    'description' => '[LOADTEST] Approved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($hasIgnoreRow) {
                    $approvedRecord['ignore_row'] = false;
                }
                $mainRecords[] = $approvedRecord;
                $statusByGeneral[$task->general_task_id] = true;
            }

            if ($refs['completed_task'] && $workorderMeta['done_at']) {
                $task = $refs['completed_task'];
                $completedRecord = [
                    'user_id' => $workorderMeta['user_id'],
                    'workorder_id' => $workorder->id,
                    'task_id' => $task->id,
                    'general_task_id' => $task->general_task_id,
                    'date_start' => $task->task_has_start_date ? Carbon::parse($workorderMeta['done_at'])->subDays(rand(1, 4))->toDateString() : null,
                    'date_finish' => Carbon::parse($workorderMeta['done_at'])->toDateString(),
                    'description' => '[LOADTEST] Completed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($hasIgnoreRow) {
                    $completedRecord['ignore_row'] = false;
                }
                $mainRecords[] = $completedRecord;
                $statusByGeneral[$task->general_task_id] = true;
            }

            foreach ($statusByGeneral as $generalTaskId => $isDone) {
                $statusRecords[] = [
                    'workorder_id' => $workorder->id,
                    'general_task_id' => $generalTaskId,
                    'is_done' => $isDone,
                    'done_at' => $isDone ? ($workorderMeta['done_at'] ?? $workorderMeta['approved_at'] ?? $workorderMeta['open_at']) : null,
                    'done_user_id' => $isDone ? $workorderMeta['user_id'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($mainRecords, 1000) as $chunk) {
            DB::table('mains')->insert($chunk);
        }

        foreach (array_chunk($statusRecords, 1000) as $chunk) {
            DB::table('workorder_general_task_statuses')->insert($chunk);
        }

        return [count($mainRecords), count($statusRecords)];
    }

    private function createTdrGraph(Collection $workorders, array $meta, array $refs, Collection $componentsByManual, Collection $processPool, string $prefix): array
    {
        $now = now();
        $tdrRecords = [];
        $tdrMeta = [];
        $hasOrderComponentId = $this->hasColumn('tdrs', 'order_component_id');
        $hasDescription = $this->hasColumn('tdrs', 'description');
        $hasTdrDescription = $this->hasColumn('tdr_processes', 'description');
        $hasNotes = $this->hasColumn('tdr_processes', 'notes');
        $hasRepairOrder = $this->hasColumn('tdr_processes', 'repair_order');
        $hasSortOrder = $this->hasColumn('tdr_processes', 'sort_order');
        $hasIgnoreRow = $this->hasColumn('tdr_processes', 'ignore_row');
        $hasInTraveler = $this->hasColumn('tdr_processes', 'in_traveler');
        $hasEc = $this->hasColumn('tdr_processes', 'ec');
        $hasUserId = $this->hasColumn('tdr_processes', 'user_id');

        foreach ($workorders as $workorder) {
            $workorderMeta = $meta[$workorder->marker] ?? null;
            if (!$workorderMeta) {
                continue;
            }
            $manualComponents = collect($componentsByManual[$workorderMeta['manual_id']] ?? []);
            $componentPool = $manualComponents->isNotEmpty() ? $manualComponents : $componentsByManual->flatten(1);
            $count = rand(1, 3);

            for ($i = 1; $i <= $count; $i++) {
                $serial = "{$prefix}-TDR-{$workorder->id}-{$i}";
                $component = $componentPool->random();
                $tdrRecord = [
                    'workorder_id' => $workorder->id,
                    'component_id' => $component->id ?? null,
                    'serial_number' => $serial,
                    'assy_serial_number' => "{$serial}-ASSY",
                    'codes_id' => $this->pickOptionalId($refs['code_ids']),
                    'conditions_id' => $this->pickOptionalId($refs['condition_ids']),
                    'necessaries_id' => $this->pickOptionalId($refs['necessary_ids']),
                    'qty' => rand(1, 5),
                    'use_tdr' => true,
                    'use_process_forms' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasOrderComponentId) {
                    $tdrRecord['order_component_id'] = $component->id ?? null;
                }

                if ($hasDescription) {
                    $tdrRecord['description'] = "[LOADTEST:{$prefix}] TDR {$serial}";
                }

                $tdrRecords[] = $tdrRecord;

                $tdrMeta[$serial] = [
                    'user_id' => $workorderMeta['user_id'],
                    'open_at' => $workorderMeta['open_at'],
                    'process_count' => rand(3, 5),
                ];
            }
        }

        foreach (array_chunk($tdrRecords, 1000) as $chunk) {
            DB::table('tdrs')->insert($chunk);
        }

        $tdrs = DB::table('tdrs')
            ->select('id', 'serial_number')
            ->where('serial_number', 'like', "{$prefix}-TDR-%")
            ->get();

        $ecProcessId = ProcessName::query()->where('name', 'EC')->value('id');
        $processRecords = [];

        foreach ($tdrs as $tdr) {
            $info = $tdrMeta[$tdr->serial_number];
            $pool = $processPool->keys()->shuffle()->take(min($info['process_count'], $processPool->count()))->values();

            if ($ecProcessId && rand(1, 100) <= 35 && !$pool->contains($ecProcessId)) {
                $pool[0] = $ecProcessId;
            }

            foreach ($pool as $sortIndex => $processNameId) {
                $processIds = collect($processPool[$processNameId] ?? [])->pluck('id')->values();
                $started = rand(1, 100) <= 70;
                $finished = $started && rand(1, 100) <= 45;
                $startDate = $started ? Carbon::parse($info['open_at'])->addDays(rand(0, 30)) : null;
                $finishDate = $finished ? (clone $startDate)->addDays(rand(1, 15)) : null;

                $processRecord = [
                    'tdrs_id' => $tdr->id,
                    'process_names_id' => $processNameId,
                    'processes' => $processIds->take(rand(1, max(1, min(3, $processIds->count()))))->values()->toJson(),
                    'date_start' => $startDate?->toDateString(),
                    'date_finish' => $finishDate?->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasTdrDescription) {
                    $processRecord['description'] = '[LOADTEST] process row';
                }
                if ($hasNotes) {
                    $processRecord['notes'] = '[LOADTEST] seeded';
                }
                if ($hasRepairOrder) {
                    $processRecord['repair_order'] = 'RO-' . rand(1000, 9999);
                }
                if ($hasSortOrder) {
                    $processRecord['sort_order'] = $sortIndex + 1;
                }
                if ($hasIgnoreRow) {
                    $processRecord['ignore_row'] = false;
                }
                if ($hasInTraveler) {
                    $processRecord['in_traveler'] = true;
                }
                if ($hasEc) {
                    $processRecord['ec'] = $ecProcessId && (int) $processNameId === (int) $ecProcessId;
                }
                if ($hasUserId) {
                    $processRecord['user_id'] = $info['user_id'];
                }

                $processRecords[] = $processRecord;
            }
        }

        foreach (array_chunk($processRecords, 1000) as $chunk) {
            DB::table('tdr_processes')->insert($chunk);
        }

        return [$tdrs->count(), count($processRecords)];
    }

    private function cleanupDataset(string $prefix): int
    {
        $this->warn("Cleaning load dataset for prefix {$prefix}");

        $workorderQuery = DB::table('workorders');

        if ($this->hasColumn('workorders', 'customer_po')) {
            $workorderQuery->where('customer_po', 'like', "{$prefix}-WO-%");
        } else {
            $workorderQuery->where('description', 'like', "[LOADTEST:{$prefix}]%");
        }

        $workorderIds = $workorderQuery->pluck('id');

        $tdrIds = DB::table('tdrs')
            ->whereIn('workorder_id', $workorderIds)
            ->orWhere('serial_number', 'like', "{$prefix}-TDR-%")
            ->pluck('id');

        $manualIds = DB::table('manuals')
            ->where('number', 'like', "{$prefix}-MAN-%")
            ->pluck('id');

        DB::table('tdr_processes')->whereIn('tdrs_id', $tdrIds)->delete();
        DB::table('tdrs')->whereIn('id', $tdrIds)->delete();
        DB::table('workorder_general_task_statuses')->whereIn('workorder_id', $workorderIds)->delete();
        DB::table('mains')->whereIn('workorder_id', $workorderIds)->delete();
        DB::table('workorders')->whereIn('id', $workorderIds)->delete();
        DB::table('manual_processes')->whereIn('manual_id', $manualIds)->delete();
        DB::table('components')->where('part_number', 'like', "{$prefix}-COMP-%")->delete();
        DB::table('units')->where('part_number', 'like', "{$prefix}-UNIT-%")->delete();
        DB::table('manuals')->whereIn('id', $manualIds)->delete();
        DB::table('processes')->where('process', 'like', "[LOADTEST:{$prefix}]%")->delete();
        DB::table('users')->where('email', 'like', strtolower("{$prefix}.user.%@example.test"))->delete();

        $this->info('Cleanup complete.');

        return self::SUCCESS;
    }

    private function pickOptionalId(array $ids): ?int
    {
        if (empty($ids) || rand(1, 100) <= 35) {
            return null;
        }

        return (int) $ids[array_rand($ids)];
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->columnCache[$table][$column]
            ??= Schema::hasColumn($table, $column);
    }
}
