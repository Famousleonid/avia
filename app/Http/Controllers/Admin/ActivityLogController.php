<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use App\Models\Scope;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $q = trim((string)$request->get('q', ''));
        $logName = (string)$request->get('log_name', 'all');
        $event = (string)$request->get('event', 'all');
        $subject = (string)$request->get('subject_type', 'all');
        $causerId = (string)$request->get('causer_id', 'all');
        $from = (string)$request->get('from', '');
        $to = (string)$request->get('to', '');
        $perPage = (int)$request->get('per_page', 50);
        $perPage = in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest('id');

        // -------------------------
        // Filters
        // -------------------------
        if ($logName !== 'all' && $logName !== '') {
            $query->where('log_name', $logName);
        }

        if ($event !== 'all' && $event !== '') {
            $query->where('event', $event);
        }

        if ($subject !== 'all' && $subject !== '') {
            $query->where('subject_type', $subject);
        }

        if ($causerId !== 'all' && $causerId !== '') {
            $query->where('causer_id', (int)$causerId);
        }

        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        // -------------------------
        // Search "по всем полям"
        // -------------------------
        if ($q !== '') {
            // защита от слишком длинных строк
            $q = Str::limit($q, 200, '');
            $qLike = "%{$q}%";
            $qAsInt = filter_var($q, FILTER_VALIDATE_INT);
            $matchedWorkorderIds = Workorder::query()
                ->where('number', 'like', $qLike)
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->all();
            if ($qAsInt !== false) {
                $matchedWorkorderIds[] = (int)$qAsInt;
            }
            $matchedWorkorderIds = array_values(array_unique(array_filter(
                $matchedWorkorderIds,
                fn($id) => $id > 0
            )));

            $query->where(function ($qq) use ($qLike, $qAsInt, $matchedWorkorderIds) {
                $qq->where('log_name', 'like', $qLike)
                    ->orWhere('event', 'like', $qLike)
                    ->orWhere('description', 'like', $qLike)
                    ->orWhere('subject_type', 'like', $qLike)
                    ->orWhere('subject_id', 'like', $qLike)
                    // properties (json/text) — самый полезный “поиск по всему”
                    ->orWhere('properties', 'like', $qLike)
                    // causer name/email
                    ->orWhereHas('causer', function ($u) use ($qLike) {
                        $u->where('name', 'like', $qLike)
                            ->orWhere('email', 'like', $qLike);
                    });

                if (!empty($matchedWorkorderIds)) {
                    $qq->orWhere(function ($wq) use ($matchedWorkorderIds) {
                        $wq->where('subject_type', Workorder::class)
                            ->whereIn('subject_id', $matchedWorkorderIds);
                    });

                    $qq->orWhere(function ($wq) use ($matchedWorkorderIds) {
                        foreach ($matchedWorkorderIds as $workorderId) {
                            $wq->orWhere('properties', 'like', '%"workorder_id":'.$workorderId.'%')
                                ->orWhere('properties', 'like', '%"workorder_id":"'.$workorderId.'"%');
                        }
                    });
                }
            });
        }

        $activities = $query->paginate($perPage)->withQueryString();

        $idBuckets = [
            'workorder_id' => [],
            'general_task_id' => [],
            'task_id' => [],
            'user_id' => [],
            'manual_id' => [],
            'manuals_id' => [],
            'component_id' => [],
            'order_component_id' => [],
            'process_names_id' => [],
            'processes_id' => [],
            'tdrs_id' => [],
            'tdr_process_id' => [],
            'codes_id' => [],
            'conditions_id' => [],
            'necessaries_id' => [],
            'builders_id' => [],
            'planes_id' => [],
            'scopes_id' => [],
            'unit_id' => [],
            'instruction_id' => [],
            'customer_id' => [],
            'done_user_id' => [],
            'notify_user_id' => [],
        ];

        foreach ($activities->items() as $activity) {
            $propsRaw = $activity->properties ?? [];
            if (is_object($propsRaw) && method_exists($propsRaw, 'toArray')) {
                $props = $propsRaw->toArray();
            } elseif (is_array($propsRaw)) {
                $props = $propsRaw;
            } else {
                $props = (array) $propsRaw;
            }

            $changes = (array)($props['changes'] ?? []);
            $old = (array)($props['old'] ?? $changes['old'] ?? []);
            $new = (array)($props['attributes'] ?? $props['new'] ?? $changes['attributes'] ?? $changes['new'] ?? []);
            $flat = array_merge($old, $new);

            foreach (array_keys($idBuckets) as $key) {
                if (!array_key_exists($key, $flat)) {
                    continue;
                }

                $val = $flat[$key];
                if (is_numeric($val) && (int)$val > 0) {
                    $idBuckets[$key][] = (int)$val;
                }
            }

            if ($activity->subject_type === Manual::class && is_numeric($activity->subject_id) && (int) $activity->subject_id > 0) {
                $idBuckets['manual_id'][] = (int) $activity->subject_id;
            }

            if ($activity->subject_type === Tdr::class && is_numeric($activity->subject_id) && (int) $activity->subject_id > 0) {
                $idBuckets['tdrs_id'][] = (int) $activity->subject_id;
            }

            if ($activity->subject_type === TdrProcess::class && is_numeric($activity->subject_id) && (int) $activity->subject_id > 0) {
                $idBuckets['tdr_process_id'][] = (int) $activity->subject_id;
            }

            if (isset($activity->subject) && isset($activity->subject->workorder_id)) {
                $subjectWorkorderId = $activity->subject->workorder_id;
                if (is_numeric($subjectWorkorderId) && (int)$subjectWorkorderId > 0) {
                    $idBuckets['workorder_id'][] = (int)$subjectWorkorderId;
                }
            }
        }

        $workorderMap = Workorder::query()
            ->whereIn('id', array_unique($idBuckets['workorder_id']))
            ->get(['id', 'number'])
            ->mapWithKeys(fn(Workorder $w) => [$w->id => (string)$w->number])
            ->all();

        $generalTaskMap = GeneralTask::query()
            ->whereIn('id', array_unique($idBuckets['general_task_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(GeneralTask $gt) => [$gt->id => (string)$gt->name])
            ->all();

        $taskMap = Task::query()
            ->whereIn('id', array_unique($idBuckets['task_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Task $t) => [$t->id => (string)$t->name])
            ->all();

        $userMap = User::query()
            ->whereIn('id', array_unique($idBuckets['user_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(User $u) => [$u->id => (string)$u->name])
            ->all();

        $manualIds = array_values(array_unique(array_merge($idBuckets['manual_id'], $idBuckets['manuals_id'])));
        $manualMap = Manual::query()
            ->whereIn('id', $manualIds)
            ->get(['id', 'number', 'lib'])
            ->mapWithKeys(fn(Manual $m) => [
                $m->id => 'manual: '.trim((string) $m->number).'   lib: '.trim((string) $m->lib),
            ])
            ->all();

        $componentIds = array_values(array_unique(array_merge($idBuckets['component_id'], $idBuckets['order_component_id'])));
        $componentMap = Component::query()
            ->whereIn('id', $componentIds)
            ->get(['id', 'name', 'part_number'])
            ->mapWithKeys(fn(Component $c) => [$c->id => trim(((string)$c->part_number).' '.$c->name)])
            ->all();

        $processNameMap = ProcessName::query()
            ->whereIn('id', array_unique($idBuckets['process_names_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(ProcessName $p) => [$p->id => (string)$p->name])
            ->all();

        $processMap = Process::query()
            ->whereIn('id', array_unique($idBuckets['processes_id']))
            ->get(['id', 'process'])
            ->mapWithKeys(fn(Process $p) => [$p->id => (string)$p->process])
            ->all();

        $tdrMap = Tdr::query()
            ->whereIn('id', array_unique($idBuckets['tdrs_id']))
            ->with(['component:id,name,part_number', 'workorder:id,number'])
            ->get(['id', 'component_id', 'workorder_id'])
            ->mapWithKeys(function (Tdr $t) {
                $componentLabel = trim(((string) ($t->component?->part_number ?? '')).' '.((string) ($t->component?->name ?? '')));
                $label = $componentLabel !== '' ? "tdr: {$componentLabel}" : "tdr id: {$t->id}";
                if ($t->workorder?->number) {
                    $label .= '   wo: '.$t->workorder->number;
                }
                return [$t->id => $label];
            })
            ->all();

        $tdrProcessMap = TdrProcess::query()
            ->whereIn('id', array_unique($idBuckets['tdr_process_id']))
            ->with([
                'tdr.component:id,name,part_number',
                'tdr.workorder:id,number',
                'processName:id,name',
            ])
            ->get(['id', 'tdrs_id', 'process_names_id'])
            ->mapWithKeys(function (TdrProcess $tdrProcess) {
                $tdr = $tdrProcess->tdr;
                $componentLabel = trim(((string) ($tdr?->component?->part_number ?? '')).' '.((string) ($tdr?->component?->name ?? '')));
                $tdrLabel = $componentLabel !== '' ? "tdr: {$componentLabel}" : null;

                if ($tdrLabel !== null && $tdr?->workorder?->number) {
                    $tdrLabel .= '   wo: '.$tdr->workorder->number;
                }

                $processLabel = trim((string) ($tdrProcess->processName?->name ?? ''));
                $parts = array_values(array_filter([$tdrLabel, $processLabel], fn ($value) => filled($value)));

                return [
                    $tdrProcess->id => $parts !== []
                        ? 'tdr process: '.implode('   process: ', $parts)
                        : 'tdr process id: '.$tdrProcess->id,
                ];
            })
            ->all();

        $codeMap = Code::query()
            ->whereIn('id', array_unique($idBuckets['codes_id']))
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(fn(Code $c) => [$c->id => trim(((string)$c->code).' '.$c->name)])
            ->all();

        $conditionMap = Condition::query()
            ->whereIn('id', array_unique($idBuckets['conditions_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Condition $c) => [$c->id => (string)$c->name])
            ->all();

        $necessaryMap = Necessary::query()
            ->whereIn('id', array_unique($idBuckets['necessaries_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Necessary $n) => [$n->id => (string)$n->name])
            ->all();

        $builderMap = Builder::query()
            ->whereIn('id', array_unique($idBuckets['builders_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Builder $b) => [$b->id => (string)$b->name])
            ->all();

        $planeMap = Plane::query()
            ->whereIn('id', array_unique($idBuckets['planes_id']))
            ->get(['id', 'type'])
            ->mapWithKeys(fn(Plane $p) => [$p->id => (string)$p->type])
            ->all();

        $scopeMap = Scope::query()
            ->whereIn('id', array_unique($idBuckets['scopes_id']))
            ->get(['id', 'scope'])
            ->mapWithKeys(fn(Scope $s) => [$s->id => (string)$s->scope])
            ->all();

        $unitMap = Unit::query()
            ->whereIn('id', array_unique($idBuckets['unit_id']))
            ->get(['id', 'name', 'part_number'])
            ->mapWithKeys(fn(Unit $u) => [$u->id => trim(((string)$u->part_number).' '.$u->name)])
            ->all();

        $instructionMap = Instruction::query()
            ->whereIn('id', array_unique($idBuckets['instruction_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Instruction $i) => [$i->id => (string)$i->name])
            ->all();

        $customerMap = Customer::query()
            ->whereIn('id', array_unique($idBuckets['customer_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Customer $c) => [$c->id => (string)$c->name])
            ->all();

        $doneUserMap = User::query()
            ->whereIn('id', array_unique($idBuckets['done_user_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(User $u) => [$u->id => (string)$u->name])
            ->all();

        $notifyUserMap = User::query()
            ->whereIn('id', array_unique($idBuckets['notify_user_id']))
            ->get(['id', 'name'])
            ->mapWithKeys(fn(User $u) => [$u->id => (string)$u->name])
            ->all();

        // -------------------------
        // Data for filter dropdowns
        // -------------------------
        $logNames = Activity::query()
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $subjectTypes = Activity::query()
            ->select('subject_type')
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $causers = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);


        return view('admin.log.index', compact(
            'activities',
            'logNames',
            'subjectTypes',
            'causers',
            'perPage',
            'workorderMap',
            'generalTaskMap',
            'taskMap',
            'userMap',
            'manualMap',
            'componentMap',
            'processNameMap',
            'processMap',
            'tdrMap',
            'tdrProcessMap',
            'codeMap',
            'conditionMap',
            'necessaryMap',
            'builderMap',
            'planeMap',
            'scopeMap',
            'unitMap',
            'instructionMap',
            'customerMap',
            'doneUserMap',
            'notifyUserMap'
        ));
    }

    public function purge(Request $request)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $data = $request->validate([
            'days' => ['required', 'integer', 'in:30,60,90,180,365,730,1095'],
        ]);

        $days = (int) $data['days'];
        $cutoff = now()->subDays($days);

        $deleted = Activity::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        activity('activity_log')
            ->causedBy(auth()->user())
            ->event('purged')
            ->withProperties([
                'days' => $days,
                'cutoff' => $cutoff->toDateTimeString(),
                'deleted_count' => $deleted,
            ])
            ->log('activity_log_purged');

        return redirect()
            ->route('admin.activity.index')
            ->with('success', "Deleted {$deleted} log entries older than {$days} days.")
            ->with('purge_deleted_count', $deleted)
            ->with('purge_days', $days);
    }
}
