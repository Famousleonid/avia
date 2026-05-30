<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Admin\TdrProcessController;
use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\Instruction;
use App\Models\Main;
use App\Models\Material;
use App\Models\MobileApiToken;
use App\Models\Necessary;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use App\Services\WorkorderNotifyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MobileApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->with(['role', 'team'])
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasVerifiedEmail()) {
            return $this->fail('Invalid credentials.', 422, [
                'email' => ['Invalid credentials.'],
            ]);
        }

        $plainToken = Str::random(80);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $data['device_name'] ?? 'iOS device',
            'token_hash' => MobileApiToken::hashPlainTextToken($plainToken),
        ]);

        return $this->ok([
            'token' => $plainToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_api_token')?->delete();

        return $this->ok(null, [], 'Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok([
            'user' => $this->userPayload($request->user()->loadMissing(['role', 'team'])),
        ]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['role', 'team']);

        return $this->ok([
            'user' => $this->userPayload($user),
            'menu_mode' => $user->roleIs('Paint') ? 'paint' : ($user->roleIs('Machining') ? 'machining' : 'workorders'),
            'media_groups' => $this->mediaGroups(),
            'date_format' => 'YYYY-MM-DD',
            'display_date_format' => 'dd.mmm.yyyy',
            'offline_mode' => false,
            'photo_upload' => [
                'compress_on_client' => false,
                'queue_on_client' => true,
                'delete_local_after_success' => true,
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['role', 'team', 'media']);

        return $this->ok([
            'profile' => $this->profilePayload($user),
            'teams' => Team::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Team $team) => $this->teamPayload($team))
                ->values(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'string'],
            'stamp' => ['required', 'string', 'max:255'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:102400'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => $data['name'],
            'phone' => $this->removeSpaces($data['phone'] ?? null),
            'birthday' => $this->parseProfileBirthday($data['birthday'] ?? null),
            'stamp' => $data['stamp'],
            'team_id' => $data['team_id'],
        ]);

        if ($request->hasFile('file')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($request->file('file'))->toMediaCollection('avatar');
        }

        $user->loadMissing(['role', 'team', 'media']);

        return $this->ok([
            'profile' => $this->profilePayload($user->fresh(['role', 'team', 'media'])),
        ], [], 'Changes saved.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'old_pass' => ['required'],
            'password' => ['required', 'confirmed', 'min:' . config('security.user_password_min')],
        ]);

        $user = $request->user();
        if (! Hash::check($data['old_pass'], $user->password)) {
            throw ValidationException::withMessages([
                'old_pass' => 'The current password is incorrect.',
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return $this->ok(null, [], 'New password saved.');
    }

    public function workorders(Request $request): JsonResponse
    {
        $user = $request->user();
        $scope = (string) $request->query('scope', 'my');
        $search = trim((string) $request->query('search', ''));
        $onlyDone = $scope === 'done' || $request->boolean('only_done');
        $includeDone = $onlyDone || $request->boolean('include_done');

        $query = Workorder::withDrafts()
            ->with(['unit.manual', 'customer', 'instruction', 'user', 'main.task'])
            ->orderByDesc('number');

        if ($scope === 'draft') {
            abort_unless($user->roleIs(['Shipping', 'Manager', 'Admin']), 403);
            $query->where('is_draft', true);
        } else {
            $query->where('is_draft', false);
            if ($scope !== 'all' && $scope !== 'done') {
                $query->where('user_id', $user->id);
            }
        }

        if ($search !== '') {
            $query->where('number', 'like', '%' . $search . '%');
        }

        $items = $query->get()
            ->filter(static function (Workorder $wo) use ($scope, $includeDone, $onlyDone) {
                if ($scope === 'draft') {
                    return true;
                }
                if ($onlyDone) {
                    return $wo->isDone();
                }

                return $includeDone || ! $wo->isDone();
            })
            ->map(fn (Workorder $wo) => $this->workorderListPayload($wo, $user))
            ->values();

        return $this->ok(['items' => $items]);
    }

    public function workorder(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId, [
            'unit.manual',
            'customer',
            'instruction',
            'user',
            'media',
        ]);

        return $this->ok([
            'workorder' => $this->workorderDetailPayload($workorder, $request->user()),
        ]);
    }

    public function updateStorage(Request $request, int $workorderId): JsonResponse
    {
        abort_unless($request->user()?->roleIs(['Shipping', 'Manager', 'Admin']), 403);

        $workorder = $this->findWorkorder($workorderId);
        $data = $request->validate([
            'storage_rack' => ['nullable', 'integer', 'min:0', 'max:999'],
            'storage_level' => ['nullable', 'integer', 'min:0', 'max:999'],
            'storage_column' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $workorder->update($data);

        return $this->ok([
            'storage' => [
                'rack' => $workorder->storage_rack,
                'level' => $workorder->storage_level,
                'column' => $workorder->storage_column,
                'location' => $workorder->storage_location,
            ],
        ]);
    }

    public function workorderMedia(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId, ['media']);
        $category = (string) $request->query('category', 'photos');

        return $this->ok([
            'media' => $this->mediaPayloads($workorder->getMedia($category)),
        ]);
    }

    public function storeWorkorderMedia(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId);
        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', 'max:102400'],
        ]);

        $category = $data['category'] ?? 'photos';
        foreach ($request->file('photos', []) as $photo) {
            $filename = 'wo_' . $workorder->number . '_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.' . $photo->getClientOriginalExtension();
            $workorder->addMedia($photo)
                ->usingFileName($filename)
                ->toMediaCollection($category);
        }

        $workorder->load('media');

        return $this->ok([
            'media' => $this->mediaPayloads($workorder->getMedia($category)),
            'photo_count' => $workorder->getMedia($category)->count(),
        ]);
    }

    public function deleteWorkorderMedia(int $workorderId, Media $media): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId);
        abort_unless(
            (int) $media->model_id === (int) $workorder->id
            && $media->model_type === $workorder->getMorphClass(),
            404
        );

        $media->delete();

        return $this->ok(['id' => $media->id]);
    }

    public function draftOptions(Request $request): JsonResponse
    {
        abort_unless($request->user()?->roleIs(['Shipping', 'Manager', 'Admin']), 403);

        return $this->ok([
            'draft_number' => Workorder::nextDraftNumber(),
            'units' => Unit::query()
                ->with('manual:id,number,lib')
                ->orderBy('part_number')
                ->get()
                ->map(fn (Unit $unit) => $this->unitPayload($unit))
                ->values(),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                ])
                ->values(),
        ]);
    }

    public function storeDraft(Request $request): JsonResponse
    {
        abort_unless($request->user()?->roleIs(['Shipping', 'Manager', 'Admin']), 403);

        $data = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'open_at' => ['nullable', 'string'],
            'customer_po' => ['nullable', 'string', 'max:255'],
            'external_damage' => ['nullable', 'boolean'],
            'received_disassembly' => ['nullable', 'boolean'],
            'disassembly_upon_arrival' => ['nullable', 'boolean'],
            'nameplate_missing' => ['nullable', 'boolean'],
            'extra_parts' => ['nullable', 'boolean'],
            'storage_rack' => ['nullable', 'integer', 'min:0', 'max:999'],
            'storage_level' => ['nullable', 'integer', 'min:0', 'max:999'],
            'storage_column' => ['nullable', 'integer', 'min:0', 'max:999'],
            'arrival_box_status' => ['nullable', 'in:ok,easy,medium,hard,replace'],
            'arrival_box_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $data['open_at'] = parse_project_date($request->input('open_at'));
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['open_at' => $e->getMessage()]);
        }

        foreach (['external_damage', 'received_disassembly', 'disassembly_upon_arrival', 'nameplate_missing', 'extra_parts'] as $key) {
            $data[$key] = $request->boolean($key);
        }

        $data['user_id'] = $request->user()->id;
        $data['instruction_id'] = Instruction::query()->firstOrCreate(['name' => 'Draft'])->id;
        $data['arrival_box_notes'] = trim((string) ($data['arrival_box_notes'] ?? '')) ?: null;

        if (! empty($data['arrival_box_status']) || $data['arrival_box_notes'] !== null) {
            $data['arrival_box_recorded_by'] = $request->user()->id;
            $data['arrival_box_recorded_at'] = now();
        }

        $workorder = Workorder::createDraft($data);
        app(WorkorderNotifyService::class)->draftCreated(
            $workorder,
            (int) $request->user()->id,
            (string) $request->user()->name
        );

        $workorder->load(['unit.manual', 'customer', 'instruction', 'user', 'media']);

        return $this->ok(['workorder' => $this->workorderDetailPayload($workorder, $request->user())], [], 'Draft created.', 201);
    }

    public function storeDraftUnit(Request $request): JsonResponse
    {
        abort_unless($request->user()?->roleIs(['Shipping', 'Manager', 'Admin']), 403);

        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $unit = Unit::query()
            ->whereNull('manual_id')
            ->where('part_number', trim($data['part_number']))
            ->first();

        if (! $unit) {
            $unit = Unit::query()->create([
                'part_number' => trim($data['part_number']),
                'manual_id' => null,
                'verified' => true,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
        }

        return $this->ok(['unit' => $this->unitPayload($unit)], [], null, $unit->wasRecentlyCreated ? 201 : 200);
    }

    public function tasks(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId, ['generalTaskStatuses']);
        $generalTasks = GeneralTask::query()->orderBy('sort_order')->orderBy('id')->get();
        $tasks = Task::query()
            ->whereIn('general_task_id', $generalTasks->pluck('id'))
            ->orderBy('general_task_id')
            ->orderBy('name')
            ->get()
            ->groupBy('general_task_id');
        $mains = Main::query()
            ->with(['user:id,name', 'task'])
            ->where('workorder_id', $workorder->id)
            ->get()
            ->keyBy('task_id');
        $gtDoneMap = $workorder->generalTaskStatuses
            ->keyBy('general_task_id')
            ->map(fn ($status) => (bool) $status->is_done);

        return $this->ok([
            'workorder' => $this->workorderMiniPayload($workorder),
            'groups' => $generalTasks->map(function (GeneralTask $group) use ($tasks, $mains, $gtDoneMap, $request) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'is_done' => (bool) ($gtDoneMap[$group->id] ?? false),
                    'tasks' => ($tasks[$group->id] ?? collect())->map(function (Task $task) use ($mains, $request) {
                        $main = $mains[$task->id] ?? null;
                        $restricted = in_array($task->name, ['Approved', 'Completed'], true);
                        $canEditFinish = ! $restricted || $request->user()->hasAnyRole('Admin|Manager');

                        return [
                            'id' => $task->id,
                            'name' => $task->name,
                            'has_start_date' => (bool) $task->task_has_start_date,
                            'restricted_finish' => $restricted,
                            'can_edit_finish' => $canEditFinish,
                            'main' => $main ? $this->mainPayload($main) : null,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function updateTaskDates(Request $request, int $workorderId, Task $task): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId);
        $data = $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
            'ignore_row' => ['nullable', 'boolean'],
        ]);

        if ($request->has('date_finish')
            && in_array($task->name, ['Approved', 'Completed'], true)
            && ! $request->user()->hasAnyRole('Admin|Manager')) {
            abort(403);
        }

        $main = Main::query()
            ->where('workorder_id', $workorder->id)
            ->where('task_id', $task->id)
            ->first();

        $ignoreRow = $request->boolean('ignore_row', (bool) ($main?->ignore_row ?? false));
        $resolved = Main::validateAndResolveDates(
            $data,
            $task,
            $main,
            $ignoreRow,
            $request->has('date_start'),
            $request->has('date_finish')
        );

        if (! $main) {
            $main = new Main();
            $main->workorder_id = $workorder->id;
            $main->task_id = $task->id;
            $main->general_task_id = $task->general_task_id;
        }

        $main->user_id = $request->user()->id;
        $main->date_start = $resolved['date_start'];
        $main->date_finish = $resolved['date_finish'];
        $main->ignore_row = $ignoreRow;
        $main->save();

        $workorder->recalcGeneralTaskStatuses($main->general_task_id);
        $workorder->syncDoneByCompletedTask();
        $main->load(['user:id,name', 'task']);

        return $this->ok(['main' => $this->mainPayload($main)]);
    }

    public function components(int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId, [
            'unit.manual',
            'tdrs.codes',
            'tdrs.necessaries',
            'tdrs.component.media',
        ]);
        $manualId = $workorder->unit?->manual_id;
        $tdrsByComponent = $workorder->tdrs
            ->filter(static fn (Tdr $tdr) => (bool) $tdr->component)
            ->groupBy('component_id');

        $attachedComponents = $tdrsByComponent
            ->map(function ($group) {
                $component = $group->first()->component;

                return $this->componentPayload($component, $group);
            })
            ->values();

        return $this->ok([
            'workorder' => $this->workorderMiniPayload($workorder),
            'components' => $attachedComponents,
            'manual_components' => $manualId
                ? Component::query()
                    ->where('manual_id', $manualId)
                    ->with('media')
                    ->orderBy('ipl_num')
                    ->orderBy('part_number')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Component $component) => $this->componentPayload($component))
                    ->values()
                : [],
            'codes' => Code::query()->orderBy('name')->get(['id', 'name'])->values(),
            'necessaries' => Necessary::query()->orderBy('name')->get(['id', 'name'])->values(),
            'conditions' => Condition::query()->where('unit', false)->orderBy('name')->get(['id', 'name'])->values(),
        ]);
    }

    public function storeComponent(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId, ['unit']);
        $manualId = $workorder->unit?->manual_id;
        if (! $manualId) {
            return $this->fail('Manual not found for selected workorder.', 422);
        }

        $data = $request->validate([
            'ipl_num' => ['required', 'string', 'max:255'],
            'part_number' => ['required', 'string', 'max:255'],
            'eff_code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'is_bush' => ['nullable', 'boolean'],
            'log_card' => ['nullable', 'boolean'],
            'bush_ipl_num' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:102400'],
        ]);

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => $data['ipl_num'],
            'part_number' => $data['part_number'],
            'eff_code' => $data['eff_code'] ?? null,
            'name' => $data['name'],
            'is_bush' => $request->boolean('is_bush'),
            'log_card' => $request->boolean('log_card'),
            'bush_ipl_num' => $request->boolean('is_bush') ? ($data['bush_ipl_num'] ?? null) : null,
        ]);

        if ($request->hasFile('photo')) {
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        $component->load('media');

        return $this->ok(['component' => $this->componentPayload($component)], [], 'Component created.', 201);
    }

    public function updateComponent(Request $request, Component $component): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'ipl_num' => ['nullable', 'string', 'max:255'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'eff_code' => ['nullable', 'string', 'max:255'],
            'is_bush' => ['nullable', 'boolean'],
            'bush_ipl_num' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->has('is_bush')) {
            $data['is_bush'] = $request->boolean('is_bush');
            if (! $data['is_bush']) {
                $data['bush_ipl_num'] = null;
            }
        }

        $component->update($data);
        $component->load('media');

        return $this->ok(['component' => $this->componentPayload($component)]);
    }

    public function storeComponentPhoto(Request $request, Component $component): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:102400'],
        ]);

        $component->clearMediaCollection('components');
        $component->addMediaFromRequest('photo')->toMediaCollection('components');
        $component->load('media');

        return $this->ok(['component' => $this->componentPayload($component)]);
    }

    public function storeComponentAttachment(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId);
        $validated = $request->validate([
            'component_id' => ['required', 'exists:components,id'],
            'code_id' => ['required', 'exists:codes,id'],
            'necessaries_id' => ['nullable', 'exists:necessaries,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        $tdr = Tdr::query()->create($this->tdrAttachData($validated, $workorder->id, (int) $validated['component_id']));
        $tdr->load(['codes', 'necessaries', 'component.media']);

        return $this->ok(['attachment' => $this->tdrPayload($tdr)], [], 'Part attached.', 201);
    }

    public function updateComponentAttachment(Request $request, Tdr $tdr): JsonResponse
    {
        $validated = $request->validate([
            'code_id' => ['required', 'exists:codes,id'],
            'necessaries_id' => ['nullable', 'exists:necessaries,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $this->tdrAttachData($validated, (int) $tdr->workorder_id, (int) $tdr->component_id);
        unset($data['workorder_id'], $data['component_id']);

        $tdr->update($data);
        $tdr->load(['codes', 'necessaries', 'component.media']);

        return $this->ok(['attachment' => $this->tdrPayload($tdr)]);
    }

    public function deleteComponentAttachment(Tdr $tdr): JsonResponse
    {
        $id = $tdr->id;
        $tdr->delete();

        return $this->ok(['id' => $id]);
    }

    public function processes(Request $request, int $workorderId): JsonResponse
    {
        $workorder = $this->findWorkorder($workorderId, [
            'unit',
            'tdrs.component.media',
            'tdrs.tdrProcesses.processName',
        ]);

        $components = $workorder->tdrs
            ->filter(static fn (Tdr $tdr) => (bool) $tdr->component)
            ->groupBy('component_id')
            ->map(function ($group) {
                $component = $group->first()->component;
                $processes = $group
                    ->flatMap(fn (Tdr $tdr) => $tdr->tdrProcesses ?? collect())
                    ->values();

                if ($processes->isEmpty()) {
                    return null;
                }

                $payload = $this->componentPayload($component);
                $payload['processes'] = $processes
                    ->map(fn (TdrProcess $process) => $this->tdrProcessPayload($process))
                    ->values();

                return $payload;
            })
            ->filter()
            ->values();

        return $this->ok([
            'workorder' => $this->workorderMiniPayload($workorder),
            'components' => $components,
        ]);
    }

    public function updateTdrProcessDates(Request $request, TdrProcess $tdrProcess)
    {
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $source = (string) $request->input('source', '');
        if ($source === 'paint') {
            $request->merge(['from_paint_index' => 1]);
        } elseif ($source === 'machining') {
            $request->merge(['from_machining_index' => 1]);
        }

        $response = app(TdrProcessController::class)->updateDate($request, $tdrProcess);
        $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;

        if (! method_exists($response, 'getData')) {
            return $response;
        }

        $payload = $response->getData(true);
        if ($status >= 400 || ! ($payload['success'] ?? false)) {
            return $this->fail($payload['message'] ?? 'Process date update failed.', $status, $payload['errors'] ?? []);
        }

        $tdrProcess->refresh()->loadMissing(['processName']);

        return $this->ok([
            'process' => $this->tdrProcessPayload($tdrProcess),
            'updated_by' => $payload['user'] ?? null,
            'date_start_user' => $payload['date_start_user'] ?? null,
            'date_finish_user' => $payload['date_finish_user'] ?? null,
            'paint_queue_changed' => (bool) ($payload['paint_queue_changed'] ?? false),
        ]);
    }

    public function materials(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $query = Material::query()->orderBy('code');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('material', 'like', "%{$search}%")
                    ->orWhere('specification', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this->ok([
            'items' => $query->get()->map(fn (Material $material) => $this->materialPayload($material))->values(),
        ]);
    }

    public function updateMaterial(Request $request, Material $material): JsonResponse
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $material->description = $data['description'] ?? '';
        $material->save();

        return $this->ok(['material' => $this->materialPayload($material)]);
    }

    public function mediaFile(Request $request, Media $media, string $variant = 'file')
    {
        abort_unless($this->canAccessMedia($request, $media), 404);

        if ($variant === 'thumb') {
            $thumbPath = $media->mime_type && str_starts_with($media->mime_type, 'image/')
                ? $media->getPath('thumb')
                : null;
            $path = $thumbPath && file_exists($thumbPath)
                ? $thumbPath
                : $media->getPath();
        } else {
            $path = $media->getPath();
        }

        if (! $path || ! file_exists($path)) {
            $path = public_path('img/noimage.png');
        }

        return response()->file($path);
    }

    private function canAccessMedia(Request $request, Media $media): bool
    {
        if ($media->model_type === (new Workorder())->getMorphClass()) {
            return true;
        }

        if ($media->model_type === (new Component())->getMorphClass()) {
            return true;
        }

        if ($media->model_type === (new User())->getMorphClass()) {
            return (int) $media->model_id === (int) $request->user()->id
                || (bool) $request->user()->isSystemAdmin();
        }

        return false;
    }

    private function tdrAttachData(array $validated, int $workorderId, int $componentId): array
    {
        $code = Code::query()->find((int) $validated['code_id']);
        $isMissing = $code && stripos((string) $code->name, 'missing') !== false;
        $useTdr = $isMissing ? 0 : 1;
        $useProcessForms = $isMissing ? 0 : 1;

        $data = [
            'workorder_id' => $workorderId,
            'component_id' => $componentId,
            'codes_id' => (int) $validated['code_id'],
            'necessaries_id' => null,
            'qty' => 1,
            'serial_number' => null,
            'order_component_id' => null,
            'use_tdr' => $useTdr,
            'use_process_forms' => $useProcessForms,
        ];

        if ($isMissing) {
            $data['qty'] = (int) ($validated['qty'] ?? 1);

            return $data;
        }

        if (! empty($validated['necessaries_id'])) {
            $data['necessaries_id'] = (int) $validated['necessaries_id'];
            $necessary = Necessary::query()->find((int) $validated['necessaries_id']);
            $necessaryName = strtolower(trim((string) ($necessary?->name ?? '')));

            if ($necessaryName === 'order new') {
                $data['use_tdr'] = 1;
                $data['use_process_forms'] = 0;
                $data['order_component_id'] = $componentId;
                $data['qty'] = (int) ($validated['qty'] ?? 1);
            } elseif (str_contains($necessaryName, 'repair')) {
                $data['serial_number'] = $validated['serial_number'] ?? null;
            } elseif (str_contains($necessaryName, 'order') && str_contains($necessaryName, 'new')) {
                $data['qty'] = (int) ($validated['qty'] ?? 1);
            }
        }

        return $data;
    }

    private function findWorkorder(int $id, array $with = []): Workorder
    {
        if (! in_array('main.task', $with, true)) {
            $with[] = 'main.task';
        }

        return Workorder::withDrafts()
            ->with($with)
            ->findOrFail($id);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roleName(),
            'team' => $user->team ? ['id' => $user->team->id, 'name' => $user->team->name] : null,
            'capabilities' => [
                'can_update_storage' => $user->roleIs(['Shipping', 'Manager', 'Admin']),
                'can_create_draft' => $user->roleIs(['Shipping', 'Manager', 'Admin']),
                'can_use_paint' => $user->roleIs(['Paint', 'Admin', 'Manager']),
                'can_use_machining' => $user->roleIs(['Machining', 'Admin', 'Manager']),
                'can_edit_restricted_task_finish' => $user->hasAnyRole('Admin|Manager'),
            ],
        ];
    }

    private function profilePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'birthday' => optional($user->birthday)?->format('Y-m-d'),
            'email' => $user->email,
            'stamp' => $user->stamp,
            'team' => $user->team ? $this->teamPayload($user->team) : null,
            'avatar' => $user->relationLoaded('media')
                ? $this->firstMediaPayload($user->media->where('collection_name', 'avatar'))
                : null,
        ];
    }

    private function teamPayload(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
        ];
    }

    private function workorderListPayload(Workorder $workorder, User $user): array
    {
        return array_merge($this->workorderMiniPayload($workorder), [
            'owned_by_current_user' => (int) $workorder->user_id === (int) $user->id,
            'customer' => $workorder->customer ? ['id' => $workorder->customer->id, 'name' => $workorder->customer->name] : null,
            'unit' => $this->unitPayload($workorder->unit),
        ]);
    }

    private function workorderMiniPayload(Workorder $workorder): array
    {
        return [
            'id' => $workorder->id,
            'number' => (int) $workorder->number,
            'number_display' => number_format((int) $workorder->number, 0, '', ' '),
            'is_draft' => (bool) $workorder->is_draft,
            'is_done' => $workorder->isDone(),
            'done_at' => optional($workorder->doneDate())?->format('Y-m-d'),
            'open_at' => optional($workorder->open_at)?->format('Y-m-d'),
            'approved' => (bool) $workorder->approve_at,
        ];
    }

    private function workorderDetailPayload(Workorder $workorder, User $user): array
    {
        return array_merge($this->workorderMiniPayload($workorder), [
            'owner' => $workorder->user ? ['id' => $workorder->user->id, 'name' => $workorder->user->name] : null,
            'serial_number' => $workorder->serial_number,
            'description' => $workorder->description,
            'customer_po' => $workorder->customer_po,
            'customer' => $workorder->customer ? ['id' => $workorder->customer->id, 'name' => $workorder->customer->name] : null,
            'instruction' => $workorder->instruction ? ['id' => $workorder->instruction->id, 'name' => $workorder->instruction->name] : null,
            'unit' => $this->unitPayload($workorder->unit),
            'approve_at' => optional($workorder->approve_at)?->format('Y-m-d'),
            'approve_name' => $workorder->approve_name ?? null,
            'storage' => [
                'rack' => $workorder->storage_rack,
                'level' => $workorder->storage_level,
                'column' => $workorder->storage_column,
                'location' => $workorder->storage_location,
                'can_update' => $user->roleIs(['Shipping', 'Manager', 'Admin']),
            ],
            'arrival_box' => [
                'status' => $workorder->arrival_box_status,
                'notes' => $workorder->arrival_box_notes,
                'recorded_by' => $workorder->arrival_box_recorded_by,
                'recorded_at' => optional($workorder->arrival_box_recorded_at)?->toIso8601String(),
            ],
            'media_groups' => collect($this->mediaGroups())->map(function ($label, $key) use ($workorder) {
                $items = $workorder->getMedia((string) $key);

                return [
                    'key' => (string) $key,
                    'label' => $label,
                    'count' => $items->count(),
                    'media' => $this->mediaPayloads($items),
                ];
            })->values(),
        ]);
    }

    private function unitPayload(?Unit $unit): ?array
    {
        if (! $unit) {
            return null;
        }

        return [
            'id' => $unit->id,
            'part_number' => $unit->part_number,
            'name' => $unit->name,
            'description' => $unit->description,
            'manual_id' => $unit->manual_id,
            'manual' => $unit->manual ? [
                'id' => $unit->manual->id,
                'number' => $unit->manual->number,
                'lib' => $unit->manual->lib ?? null,
            ] : null,
            'verified' => (bool) $unit->verified,
        ];
    }

    private function componentPayload(?Component $component, $tdrs = null): ?array
    {
        if (! $component) {
            return null;
        }

        return [
            'id' => $component->id,
            'name' => $component->name,
            'ipl_num' => $component->ipl_num,
            'part_number' => $component->part_number,
            'eff_code' => $component->eff_code,
            'is_bush' => (bool) $component->is_bush,
            'bush_ipl_num' => $component->bush_ipl_num,
            'log_card' => (bool) $component->log_card,
            'text' => trim(($component->ipl_num ?: '-') . ' | ' . ($component->part_number ?: '-') . ' | ' . ($component->name ?: '')),
            'photo' => $component->relationLoaded('media') ? $this->firstMediaPayload($component->media->where('collection_name', 'components')) : null,
            'tdrs' => $tdrs ? collect($tdrs)->map(fn (Tdr $tdr) => $this->tdrPayload($tdr))->values() : [],
        ];
    }

    private function tdrPayload(Tdr $tdr): array
    {
        return [
            'id' => $tdr->id,
            'component_id' => $tdr->component_id,
            'code_id' => $tdr->codes_id,
            'code_name' => $tdr->codes?->name,
            'necessaries_id' => $tdr->necessaries_id,
            'necessaries_name' => $tdr->necessaries?->name,
            'qty' => $tdr->qty,
            'serial_number' => $tdr->serial_number,
            'use_tdr' => (bool) $tdr->use_tdr,
            'use_process_forms' => (bool) $tdr->use_process_forms,
        ];
    }

    private function tdrProcessPayload(TdrProcess $process): array
    {
        return [
            'id' => $process->id,
            'name' => $process->processName?->name,
            'process_name_id' => $process->process_names_id,
            'description' => $process->description,
            'notes' => $process->notes,
            'repair_order' => $process->repair_order,
            'date_start' => optional($process->date_start)?->format('Y-m-d'),
            'date_finish' => optional($process->date_finish)?->format('Y-m-d'),
            'date_promise' => optional($process->date_promise)?->format('Y-m-d'),
        ];
    }

    private function mainPayload(Main $main): array
    {
        return [
            'id' => $main->id,
            'task_id' => $main->task_id,
            'general_task_id' => $main->general_task_id,
            'date_start' => optional($main->date_start)?->format('Y-m-d'),
            'date_finish' => optional($main->date_finish)?->format('Y-m-d'),
            'ignore_row' => (bool) $main->ignore_row,
            'user' => $main->user ? ['id' => $main->user->id, 'name' => $main->user->name] : null,
        ];
    }

    private function materialPayload(Material $material): array
    {
        return [
            'id' => $material->id,
            'code' => $material->code,
            'material' => $material->material,
            'specification' => $material->specification,
            'description' => $material->description,
        ];
    }

    private function mediaPayloads($media): array
    {
        return collect($media)->map(fn (Media $item) => $this->mediaPayload($item))->values()->all();
    }

    private function firstMediaPayload($media): ?array
    {
        $first = collect($media)->first();

        return $first ? $this->mediaPayload($first) : null;
    }

    private function mediaPayload(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'collection' => $media->collection_name,
            'thumb_url' => route('api.mobile.media.thumb', $media),
            'url' => route('api.mobile.media.file', $media),
            'created_at' => optional($media->created_at)?->toIso8601String(),
        ];
    }

    private function mediaGroups(): array
    {
        $groups = config('workorder_media.groups', ['photos' => 'Photos']);

        return is_array($groups) && $groups !== [] ? $groups : ['photos' => 'Photos'];
    }

    private function parseProfileBirthday(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                $date = Carbon::createFromFormat('Y-m-d', $raw);
            } elseif (preg_match('/^\d{2}[\/.][a-z]{3}[\/.]\d{4}$/i', $raw)) {
                $raw = str_replace('.', '/', $raw);
                $normalized = preg_replace_callback(
                    '/\/(\w{3})\//',
                    static fn (array $m): string => '/' . ucfirst(strtolower((string) $m[1])) . '/',
                    $raw
                );
                $date = Carbon::createFromFormat('d/M/Y', (string) $normalized);
            } else {
                throw ValidationException::withMessages([
                    'birthday' => 'Birthday format must be YYYY-MM-DD or dd/mmm/yyyy.',
                ]);
            }
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'birthday' => 'Invalid birthday date.',
            ]);
        }

        if ($date->startOfDay()->gt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'birthday' => 'Birthday cannot be later than today.',
            ]);
        }

        return $date->format('Y-m-d');
    }

    private function removeSpaces(?string $value): ?string
    {
        return $value === null ? null : str_replace(' ', '', $value);
    }

    private function ok($data = [], array $meta = [], ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $data,
            'meta' => (object) $meta,
            'message' => $message,
        ], $status);
    }

    private function fail(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }
}
