<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Admin\MachiningController as AdminMachiningController;
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
use App\Models\MachiningWorkStep;
use App\Models\Paint;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Notifications\NewMessageNotification;
use App\Services\MachiningListingRowsBuilder;
use App\Services\PaintIndexRowsBuilder;
use App\Services\WorkorderNotifyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MobileApiController extends Controller
{
    public function publicAppConfig(): JsonResponse
    {
        $forgotPasswordUrl = \Illuminate\Support\Facades\Route::has('password.request')
            ? route('password.request')
            : null;

        return $this->ok([
            'app' => [
                'name' => (string) config('app.name', 'Aviatechnik'),
                'bundle_display_name' => (string) config('app.name', 'Aviatechnik'),
                'theme' => 'dark',
                'logo' => [
                    'favicon_url' => asset('img/favicon.webp'),
                    'hero_image_url' => asset('img/avia190.png'),
                ],
                'background' => [
                    'gradient_start' => 'blue',
                    'gradient_end' => 'deepskyblue',
                    'hero_image_mobile_width' => 300,
                    'hero_image_desktop_width' => 700,
                ],
            ],
            'auth' => [
                'login_title' => 'Login',
                'email_label' => 'Email Address',
                'password_label' => 'Password',
                'submit_label' => 'Login',
                'remember_me_supported' => true,
                'remember_me_mode' => 'client_token_persistence',
                'forgot_password_supported' => true,
                'forgot_password_url' => $forgotPasswordUrl,
                'show_close_button' => true,
            ],
            'launch' => [
                'show_splash' => true,
                'initial_route' => 'login',
            ],
        ]);
    }

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
            'available_menu_modes' => $this->availableMenuModes($user),
            'media_groups' => $this->mediaGroups(),
            'date_format' => 'YYYY-MM-DD',
            'display_date_format' => 'dd/mmm/yyyy',
            'offline_mode' => false,
            'photo_upload' => [
                'compress_on_client' => false,
                'queue_on_client' => true,
                'delete_local_after_success' => true,
            ],
            'navigation' => $this->navigationPayload($user),
            'screens' => $this->screenCatalogPayload($user),
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

        $unit = Unit::query()->find($data['unit_id']);
        $submittedUnitName = trim((string) ($data['description'] ?? ''));
        $existingUnitName = trim((string) ($unit?->name ?? ''));
        $description = $submittedUnitName !== '' ? $submittedUnitName : $existingUnitName;
        $data['description'] = $description !== '' ? $description : null;
        $unit?->forceFill(['name' => $description !== '' ? $description : null])->save();

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
            'log_card' => ['nullable', 'boolean'],
            'bush_ipl_num' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->has('is_bush')) {
            $data['is_bush'] = $request->boolean('is_bush');
            if (! $data['is_bush']) {
                $data['bush_ipl_num'] = null;
            }
        }
        if ($request->has('log_card')) {
            $data['log_card'] = $request->boolean('log_card');
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

    public function paint(Request $request): JsonResponse
    {
        abort_unless($request->user()?->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $workorders = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->with([
                'user:id,name',
                'unit.manual.plane:id,type',
                'tdrs' => function ($q) {
                    $q->with([
                        'component:id,part_number,name,ipl_num',
                        'tdrProcesses.processName',
                    ]);
                },
            ])
            ->orderByRaw('CASE WHEN paint_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('paint_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        $rows = app(PaintIndexRowsBuilder::class)->build($workorders);
        $lostParts = Paint::query()
            ->with(['user:id,name', 'media'])
            ->latest()
            ->limit(100)
            ->get();
        $activeTab = $request->query('tab', 'wo') === 'lost' ? 'lost' : 'wo';

        return $this->ok([
            'menu_mode' => 'paint',
            'active_tab' => $activeTab,
            'tabs' => [
                ['key' => 'wo', 'label' => 'WO'],
                ['key' => 'lost', 'label' => 'Lost'],
            ],
            'top_menu' => $this->topMenuForMode($request->user(), 'paint'),
            'rows' => collect($rows)->map(fn (object $row) => $this->paintRowPayload($row))->values(),
            'lost_parts' => $lostParts->map(fn (Paint $paint) => $this->paintLostPayload($paint))->values(),
            'ui_state' => [
                'hide_closed_default' => false,
                'owner_message_max_length' => 1000,
            ],
        ]);
    }

    public function storePaintLost(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $validated = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $paint = Paint::query()->create([
            'user_id' => $user->id,
            'part_number' => trim($validated['part_number']),
            'serial_number' => trim((string) ($validated['serial_number'] ?? '')) ?: null,
            'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
        ]);

        $paint->addMediaFromRequest('photo')->toMediaCollection('lost');
        $paint->load(['user:id,name', 'media']);

        return $this->ok([
            'lost_part' => $this->paintLostPayload($paint),
        ], [], 'Lost part added.', 201);
    }

    public function deletePaintLost(Request $request, Paint $paint): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $mediaIds = $paint->media()->pluck('id')->map(static fn ($id) => (int) $id)->values()->all();
        activity('paint_lost_delete')
            ->causedBy($user)
            ->performedOn($paint)
            ->event('deleted')
            ->withProperties([
                'paint_id' => (int) $paint->id,
                'part_number' => (string) ($paint->part_number ?? ''),
                'serial_number' => (string) ($paint->serial_number ?? ''),
                'comment' => (string) ($paint->comment ?? ''),
                'owner_user_id' => (int) ($paint->user_id ?? 0),
                'media_ids' => $mediaIds,
                'source' => 'api.mobile.paint.lost',
            ])
            ->log('Paint lost image deleted');

        $id = (int) $paint->id;
        $paint->delete();

        return $this->ok(['id' => $id]);
    }

    public function sendPaintOwnerMessage(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        if ((int) $data['user_id'] === (int) $user->id) {
            return $this->fail('You cannot send message to yourself.', 422, [
                'user_id' => ['You cannot send message to yourself.'],
            ]);
        }

        $recipient = User::query()->findOrFail((int) $data['user_id']);
        $prefs = $recipient->notification_prefs ?? [];
        $mutedUsers = $prefs['muted_users'] ?? [];
        if (in_array((int) $user->id, $mutedUsers, true)) {
            return $this->fail('Recipient blocked you.', 403, [
                'user_id' => ['Recipient blocked you.'],
            ]);
        }

        $recipient->notify(new NewMessageNotification(
            fromUserId: (int) $user->id,
            fromName: (string) $user->name,
            text: (string) $data['message']
        ));

        return $this->ok([
            'recipient' => ['id' => $recipient->id, 'name' => $recipient->name],
        ], [], 'Message sent.');
    }

    public function machining(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $onlyMyWo = $request->boolean('my_wo');
        $workorders = $this->mobileMachiningWorkordersQuery()->get();
        $rows = $this->buildMobileMachiningFilteredRows($workorders, $user, $onlyMyWo)
            ->filter(static fn (object $row) => ! self::mobileMachiningDatePresent($row->date_finish ?? null))
            ->values();
        $woList = $this->aggregateMobileMachiningWorkorderList($rows);

        return $this->ok([
            'menu_mode' => 'machining',
            'top_menu' => $this->topMenuForMode($user, 'machining'),
            'my_wo' => $onlyMyWo,
            'items' => $woList->map(fn (object $entry) => [
                'workorder' => $this->workorderMiniPayload($entry->workorder),
                'queue_display' => $entry->queue_display,
            ])->values(),
            'ui_state' => [
                'show_my_wo_toggle' => true,
            ],
        ]);
    }

    public function machiningWorkorder(Request $request, int $workorderId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $workorder = Workorder::query()
            ->with(array_merge([
                'user:id,name',
                'customer:id,name',
            ], $this->mobileMachiningRelations()))
            ->findOrFail($workorderId);
        abort_unless($workorder->isOpenForMachiningBoard(), 404);

        $ctx = $this->mobileMachiningWorkorderContextCore($workorder, $user, $request->boolean('my_wo'));
        abort_if($ctx === null, 403, 'This work order is not on the machining board or has no machining steps.');

        $stepMachinistIds = $ctx['detailItems']
            ->flatMap(static function ($item) {
                if (($item->kind ?? '') === 'step_group' && isset($item->steps)) {
                    return $item->steps;
                }

                return collect([$item]);
            })
            ->filter(static fn ($item) => ($item->kind ?? '') === 'step' && ($item->step ?? null) !== null)
            ->map(static fn ($item) => (int) ($item->display_machinist_user_id ?? $item->step->machinist_user_id ?? 0))
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values();

        $machinistNames = [];
        if ($stepMachinistIds->isNotEmpty()) {
            $machinistNames = User::query()
                ->withTrashed()
                ->whereIn('id', $stepMachinistIds->all())
                ->get(['id', 'name'])
                ->mapWithKeys(static fn (User $u) => [(int) $u->id => trim((string) ($u->name ?? ''))])
                ->all();
        }

        return $this->ok([
            'menu_mode' => 'machining',
            'top_menu' => $this->topMenuForMode($user, 'machining'),
            'workorder' => $this->workorderMiniPayload($ctx['workorder']),
            'detail_items' => $ctx['detailItems']->map(fn (object $item) => $this->machiningDetailItemPayload($item, $user, $machinistNames))->values(),
            'media' => [
                'machining_photo_count' => $this->workorderMediaCount($ctx['workorder'], 'Machining', 'image/'),
                'pdf_count' => $this->workorderMediaCount($ctx['workorder'], 'pdfs'),
            ],
            'ui_state' => [
                'show_hide_closed_toggle' => true,
            ],
        ]);
    }

    public function updateMachiningStep(Request $request, MachiningWorkStep $machiningWorkStep): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);
        abort_unless((int) ($machiningWorkStep->machinist_user_id ?? 0) === (int) $user->id && (int) $user->id > 0, 403);

        $response = app(AdminMachiningController::class)->updateMachiningWorkStep($request, $machiningWorkStep);
        if (! $response instanceof JsonResponse) {
            return $this->fail('Step update failed.', 500);
        }

        $payload = $response->getData(true);
        $status = $response->getStatusCode();
        if ($status >= 400 || ! ($payload['success'] ?? false)) {
            return $this->fail($payload['message'] ?? 'Step update failed.', $status, $payload['errors'] ?? []);
        }

        $machiningWorkStep->refresh();

        return $this->ok([
            'step' => $this->machiningStepPayload($machiningWorkStep),
            'date_start' => optional($machiningWorkStep->date_start)?->format('Y-m-d'),
            'date_finish' => optional($machiningWorkStep->date_finish)?->format('Y-m-d'),
        ]);
    }

    public function storeMachiningWorkorderPhoto(Request $request, int $workorderId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $workorder = Workorder::query()->findOrFail($workorderId);
        abort_unless($workorder->isOpenForMachiningBoard(), 404);
        abort_unless($this->mobileMachiningWorkorderContextCore($workorder, $user, false) !== null, 403);

        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', 'max:15360'],
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $filename = 'wo_' . $workorder->number . '_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.' . $photo->getClientOriginalExtension();
            $workorder->addMedia($photo)
                ->usingFileName($filename)
                ->toMediaCollection('Machining');
        }

        return $this->ok([
            'machining_photo_count' => $this->workorderMediaCount($workorder, 'Machining', 'image/'),
            'pdf_count' => $this->workorderMediaCount($workorder, 'pdfs'),
        ]);
    }

    public function machiningWorkorderPhotos(Request $request, int $workorderId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $workorder = Workorder::query()->findOrFail($workorderId);
        abort_unless($workorder->isOpenForMachiningBoard(), 404);
        abort_unless($this->mobileMachiningWorkorderContextCore($workorder, $user, false) !== null, 403);

        $photos = collect();
        foreach ($workorder->getMedia('Machining') as $media) {
            if (! $media->mime_type || ! str_starts_with($media->mime_type, 'image/')) {
                continue;
            }
            $photos->push($this->mediaPayload($media));
        }

        return $this->ok(['items' => $photos->values()]);
    }

    public function storeMachiningWorkorderDocPdf(Request $request, int $workorderId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $workorder = Workorder::query()->findOrFail($workorderId);
        abort_unless($workorder->isOpenForMachiningBoard(), 404);
        abort_unless($this->mobileMachiningWorkorderContextCore($workorder, $user, false) !== null, 403);

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $file = $request->file('image');
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();
        $raw = $path && is_readable($path) ? (string) file_get_contents($path) : (string) $file->getContent();
        if ($raw === '') {
            return $this->fail('Could not read the uploaded image.', 422);
        }

        try {
            $binary = $this->buildMachiningWorkorderDocPdfBinary($raw, $mime);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Failed to build PDF. Try a smaller image.', 500);
        }

        $filename = 'wo_' . $workorder->number . '_machining_doc_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.pdf';
        $label = 'Machining doc ' . now()->format('Y-m-d H:i');

        $media = $workorder
            ->addMediaFromString($binary)
            ->usingFileName($filename)
            ->toMediaCollection('pdfs');
        $media->setCustomProperty('document_name', $label);
        $media->setCustomProperty('source', 'mobile_machining_doc');
        $media->name = $label;
        $media->save();

        return $this->ok([
            'media' => $this->mediaPayload($media),
            'machining_photo_count' => $this->workorderMediaCount($workorder, 'Machining', 'image/'),
            'pdf_count' => $this->workorderMediaCount($workorder, 'pdfs'),
        ]);
    }

    public function machiningWorkorderPdfs(Request $request, int $workorderId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $workorder = Workorder::query()->findOrFail($workorderId);
        abort_unless($workorder->isOpenForMachiningBoard(), 404);
        abort_unless($this->mobileMachiningWorkorderContextCore($workorder, $user, false) !== null, 403);

        $items = $workorder->getMedia('pdfs')->map(function ($media) {
            $documentName = $media->getCustomProperty('document_name') ?: ($media->name ?? null);
            return array_merge($this->mediaPayload($media), [
                'label' => $documentName ?: $media->file_name,
            ]);
        })->values();

        return $this->ok(['items' => $items]);
    }

    public function deleteMachiningWorkorderMedia(Request $request, int $workorderId, Media $media): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->roleIs(['Machining', 'Admin', 'Manager']), 403);

        $workorder = Workorder::query()->findOrFail($workorderId);
        abort_unless($workorder->isOpenForMachiningBoard(), 404);
        abort_unless($this->mobileMachiningWorkorderContextCore($workorder, $user, false) !== null, 403);

        abort_unless(
            (int) $media->model_id === (int) $workorder->id
            && $media->model_type === $workorder->getMorphClass()
            && in_array($media->collection_name, ['Machining', 'pdfs'], true),
            404
        );
        if ($media->collection_name === 'Machining') {
            abort_unless($media->mime_type && str_starts_with($media->mime_type, 'image/'), 404);
        }

        $id = (int) $media->id;
        $media->delete();

        return $this->ok(['id' => $id]);
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
            'description' => $workorder->displayDescription(),
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

    private function navigationPayload(User $user): array
    {
        $workorderDetailMenu = [
            ['key' => 'workorder', 'label' => 'Workorder', 'screen' => 'workorder_detail'],
        ];
        if (! $user->roleIs('Shipping')) {
            $workorderDetailMenu[] = ['key' => 'tasks', 'label' => 'Tasks', 'screen' => 'workorder_tasks'];
            $workorderDetailMenu[] = ['key' => 'parts', 'label' => 'Parts', 'screen' => 'workorder_parts'];
            $workorderDetailMenu[] = ['key' => 'process', 'label' => 'Process', 'screen' => 'workorder_process'];
        }

        return [
            'top_menu' => $this->topMenuForMode($user, 'workorders'),
            'top_menu_modes' => [
                'workorders' => $this->topMenuForMode($user, 'workorders'),
                'paint' => $this->topMenuForMode($user, 'paint'),
                'machining' => $this->topMenuForMode($user, 'machining'),
            ],
            'workorder_detail_menu' => $workorderDetailMenu,
            'available_sections' => $this->availableSectionsPayload($user),
        ];
    }

    private function screenCatalogPayload(User $user): array
    {
        return [
            'splash' => [
                'theme' => 'dark',
                'logo_favicon_url' => asset('img/favicon.webp'),
            ],
            'login' => [
                'title' => 'Login',
                'fields' => ['email', 'password'],
            ],
            'workorders' => [
                'filters' => ['search', 'all', 'done', 'draft'],
            ],
            'materials' => [
                'enabled' => ! $user->roleIs('Shipping'),
                'editable_fields' => ['description'],
                'search_min_length' => 2,
            ],
            'draft_create' => [
                'enabled' => $user->roleIs(['Shipping', 'Manager', 'Admin']),
                'box_statuses' => [
                    ['key' => 'ok', 'label' => 'OK'],
                    ['key' => 'easy', 'label' => 'Easy repair'],
                    ['key' => 'medium', 'label' => 'Medium repair'],
                    ['key' => 'hard', 'label' => 'Hard repair'],
                    ['key' => 'replace', 'label' => 'Replace'],
                ],
                'visible_flags' => [
                    'external_damage',
                    'nameplate_missing',
                ],
                'supported_api_flags' => [
                    'external_damage',
                    'received_disassembly',
                    'disassembly_upon_arrival',
                    'nameplate_missing',
                    'extra_parts',
                ],
                'pending_unit_quick_fields' => ['part_number'],
                'box_notes_max_length' => 1000,
            ],
            'workorder_parts' => [
                'component_create_fields' => ['ipl_num', 'part_number', 'name', 'is_bush', 'log_card', 'bush_ipl_num', 'photo'],
                'component_edit_fields' => ['name', 'ipl_num', 'part_number', 'eff_code', 'is_bush', 'log_card', 'bush_ipl_num'],
            ],
            'paint_workorders' => [
                'enabled' => $user->roleIs(['Paint', 'Admin', 'Manager']),
                'supports_owner_message' => true,
                'supports_hide_closed' => true,
            ],
            'machining_workorders' => [
                'enabled' => $user->roleIs(['Machining', 'Admin', 'Manager']),
                'supports_my_wo_toggle' => true,
                'supports_hide_closed' => true,
            ],
        ];
    }

    private function availableMenuModes(User $user): array
    {
        $modes = ['workorders'];
        if ($user->roleIs(['Paint', 'Admin', 'Manager'])) {
            $modes[] = 'paint';
        }
        if ($user->roleIs(['Machining', 'Admin', 'Manager'])) {
            $modes[] = 'machining';
        }

        return $modes;
    }

    private function topMenuForMode(User $user, string $mode): array
    {
        if ($mode === 'paint') {
            return [
                ['key' => 'wo', 'label' => 'WO', 'screen' => 'paint_workorders'],
                ['key' => 'lost', 'label' => 'Lost', 'screen' => 'paint_lost'],
                ['key' => 'profile', 'label' => 'Profile', 'screen' => 'profile'],
                ['key' => 'logout', 'label' => 'Logout', 'action' => 'logout'],
            ];
        }

        if ($mode === 'machining') {
            return [
                ['key' => 'wo', 'label' => 'WO', 'screen' => 'machining_workorders'],
                ['key' => 'my_wo', 'label' => 'My WO', 'control' => 'toggle'],
                ['key' => 'profile', 'label' => 'Profile', 'screen' => 'profile'],
                ['key' => 'logout', 'label' => 'Logout', 'action' => 'logout'],
            ];
        }

        $topMenu = [
            ['key' => 'wo', 'label' => 'WO', 'screen' => 'workorders'],
        ];
        if (! $user->roleIs('Shipping')) {
            $topMenu[] = ['key' => 'material', 'label' => 'Material', 'screen' => 'materials'];
            $topMenu[] = ['key' => 'profile', 'label' => 'Profile', 'screen' => 'profile'];
        }
        if ($user->roleIs(['Shipping', 'Manager', 'Admin'])) {
            $topMenu[] = ['key' => 'create_draft', 'label' => 'Create Draft', 'screen' => 'draft_create'];
        }
        $topMenu[] = ['key' => 'logout', 'label' => 'Logout', 'action' => 'logout'];

        return $topMenu;
    }

    private function availableSectionsPayload(User $user): array
    {
        $sections = [
            ['key' => 'workorders', 'enabled' => true],
            ['key' => 'materials', 'enabled' => ! $user->roleIs('Shipping')],
            ['key' => 'draft_create', 'enabled' => $user->roleIs(['Shipping', 'Manager', 'Admin'])],
            ['key' => 'paint', 'enabled' => $user->roleIs(['Paint', 'Admin', 'Manager'])],
            ['key' => 'machining', 'enabled' => $user->roleIs(['Machining', 'Admin', 'Manager'])],
            ['key' => 'profile', 'enabled' => ! $user->roleIs('Shipping') || $user->roleIs(['Paint', 'Machining'])],
        ];

        return array_values(array_filter($sections, static fn (array $section): bool => $section['enabled']));
    }

    private function paintRowPayload(object $row): array
    {
        $workorder = $row->workorder;
        $editTp = $row->edit_paint_process ?? null;
        $lineStart = $editTp?->date_start ?? $row->date_start;
        $lineFinish = $editTp?->date_finish ?? $row->date_finish;
        $queuePosition = $workorder->paint_queue_order !== null ? (int) ($row->paint_queue_position ?? 0) : null;
        $isMaster = (bool) ($row->is_queue_master ?? false);

        return [
            'workorder' => $this->workorderMiniPayload($workorder),
            'detail_label' => $row->detail_label ?? '—',
            'is_queue_master' => $isMaster,
            'queue_position' => $queuePosition,
            'queue_display' => $queuePosition !== null && $isMaster ? str_pad((string) $queuePosition, 2, '0', STR_PAD_LEFT) : ($queuePosition !== null ? '' : '—'),
            'owner' => $isMaster && $workorder->user ? ['id' => $workorder->user->id, 'name' => $workorder->user->name] : null,
            'start_date' => optional($lineStart)?->format('Y-m-d'),
            'finish_date' => optional($lineFinish)?->format('Y-m-d'),
            'editable_process_id' => $editTp?->id,
            'closed' => self::mobileMachiningDatePresent($lineStart) && self::mobileMachiningDatePresent($lineFinish),
        ];
    }

    private function paintLostPayload(Paint $paint): array
    {
        return [
            'id' => $paint->id,
            'part_number' => $paint->part_number,
            'serial_number' => $paint->serial_number,
            'comment' => $paint->comment,
            'owner' => $paint->user ? ['id' => $paint->user->id, 'name' => $paint->user->name] : null,
            'photo' => $this->firstMediaPayload($paint->getMedia('lost')),
        ];
    }

    private function machiningDetailItemPayload(object $item, User $user, array $machinistNames): array
    {
        if (($item->kind ?? '') === 'step_group') {
            return [
                'kind' => 'step_group',
                'detail_name' => $item->detail_name,
                'detail_label' => $item->detail_label,
                'detail_serial' => $item->detail_serial,
                'date_parent' => optional($item->date_parent?->date_start)?->format('Y-m-d'),
                'processes_label' => $item->processes_label ?? '',
                'steps' => $item->steps->map(fn (object $stepItem) => $this->machiningDetailItemPayload($stepItem, $user, $machinistNames))->values(),
            ];
        }

        if (($item->kind ?? '') === 'pending_steps') {
            return [
                'kind' => 'pending_steps',
                'detail_name' => $item->detail_name,
                'detail_label' => $item->detail_label,
                'detail_serial' => $item->detail_serial,
                'date_parent' => optional($item->date_parent?->date_start)?->format('Y-m-d'),
                'processes_label' => $item->processes_label ?? '',
            ];
        }

        $step = $item->step;
        $displayMachinistId = (int) ($item->display_machinist_user_id ?? $step->machinist_user_id ?? 0);
        return [
            'kind' => 'step',
            'detail_name' => $item->detail_name,
            'detail_label' => $item->detail_label,
            'detail_serial' => $item->detail_serial,
            'date_parent' => optional($item->date_parent?->date_start)?->format('Y-m-d'),
            'processes_label' => $item->processes_label ?? '',
            'step' => $this->machiningStepPayload($step),
            'effective_step_start' => optional($item->effective_step_start ?? null)?->format('Y-m-d'),
            'display_machinist' => $displayMachinistId > 0 ? [
                'id' => $displayMachinistId,
                'name' => $machinistNames[$displayMachinistId] ?? ('user #' . $displayMachinistId),
            ] : null,
            'can_edit' => (int) ($step->machinist_user_id ?? 0) === (int) $user->id,
        ];
    }

    private function machiningStepPayload(MachiningWorkStep $step): array
    {
        return [
            'id' => $step->id,
            'step_index' => (int) $step->step_index,
            'machinist_user_id' => $step->machinist_user_id ? (int) $step->machinist_user_id : null,
            'date_start' => optional($step->date_start)?->format('Y-m-d'),
            'date_finish' => optional($step->date_finish)?->format('Y-m-d'),
            'description' => $step->description,
        ];
    }

    private function mobileMachiningRelations(): array
    {
        return [
            'unit' => function ($q) {
                $q->select('id', 'part_number', 'name', 'manual_id')
                    ->with(['manual.plane:id,type']);
            },
            'tdrs' => function ($q) {
                $q->with([
                    'component:id,part_number,name,ipl_num',
                    'tdrProcesses.processName',
                    'tdrProcesses.machiningWorkSteps.machinist:id,name',
                ]);
            },
            'woBushingProcesses' => function ($q) {
                $q->with([
                    'line.component',
                    'process.process_name',
                    'batch.machiningWorkSteps.machinist:id,name',
                    'machiningWorkSteps.machinist:id,name',
                ]);
            },
        ];
    }

    private function mobileMachiningWorkordersQuery()
    {
        return Workorder::query()
            ->whereNull('done_at')
            ->whereMachiningHasDateSent()
            ->with(array_merge([
                'user:id,name',
                'customer:id,name',
            ], $this->mobileMachiningRelations()))
            ->orderByRaw('CASE WHEN machining_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('machining_queue_order', 'asc')
            ->orderBy('number', 'asc');
    }

    private function buildMobileMachiningFilteredRows(Collection $workorders, ?User $user = null, bool $onlyMyMachiningSteps = false): Collection
    {
        $user = $user ?? auth()->user();
        $rows = app(MachiningListingRowsBuilder::class)->build($workorders);
        $uid = (int) ($user?->id ?? 0);

        if ($onlyMyMachiningSteps && $uid > 0) {
            $rows = $rows->filter(static fn (object $row) => self::mobileMachiningRowHasAssignedStepForUser($row, $uid));
        }

        return $rows->values();
    }

    private function aggregateMobileMachiningWorkorderList(Collection $rows): Collection
    {
        $seen = [];
        $woList = collect();
        foreach ($rows as $row) {
            $wid = (int) $row->workorder->id;
            if (isset($seen[$wid])) {
                continue;
            }
            $seen[$wid] = true;
            $wo = $row->workorder;
            $rowHasDateFinish = self::mobileMachiningDatePresent($row->date_finish ?? null);
            $showQueueNum = $wo->machining_queue_order !== null && ! $rowHasDateFinish;
            $qPos = (int) ($row->machining_queue_position ?? 0);
            $queueCellText = ($showQueueNum && $qPos > 0) ? str_pad((string) $qPos, 2, '0', STR_PAD_LEFT) : '—';
            $queueSort = ($showQueueNum && $qPos > 0) ? $qPos : PHP_INT_MAX;
            $woList->push((object) [
                'workorder' => $wo,
                'queue_display' => $queueCellText,
                'queue_sort' => $queueSort,
            ]);
        }

        return $woList->sort(function ($a, $b) {
            if ($a->queue_sort !== $b->queue_sort) {
                return $a->queue_sort <=> $b->queue_sort;
            }

            return (int) $a->workorder->number <=> (int) $b->workorder->number;
        })->values();
    }

    private function mobileMachiningWorkorderContextCore(Workorder $workorder, User $user, bool $onlyMine = false): ?array
    {
        $rows = $this->buildMobileMachiningFilteredRows(collect([$workorder]), $user, false)->values();
        if ($rows->isEmpty()) {
            return null;
        }

        $uid = (int) ($user->id ?? 0);
        if ($onlyMine && $uid > 0) {
            $rows = $rows
                ->filter(static fn (object $row) => self::mobileMachiningRowHasAssignedStepForUser($row, $uid))
                ->values();
            if ($rows->isEmpty()) {
                return null;
            }
        }

        $restrictStepsToMachinistId = ($onlyMine && $uid > 0) ? $uid : null;
        $detailItems = $this->buildMobileMachiningWorkorderDetailItems($rows, $restrictStepsToMachinistId);
        if ($detailItems->isEmpty()) {
            return null;
        }

        $machiningProcessCatalog = $this->mobileMachiningProcessCatalogForWorkorder($workorder);
        foreach ($detailItems as $item) {
            if (($item->kind ?? '') === 'pending_steps') {
                $item->processes_label = self::mobileMachiningProcessesLabelForRow($item->row, $machiningProcessCatalog);
                continue;
            }
            if (($item->kind ?? '') === 'step') {
                $item->processes_label = self::mobileMachiningProcessesLabelForRow($item->row, $machiningProcessCatalog);
            }
        }

        return [
            'workorder' => $workorder,
            'detailItems' => $this->groupMobileMachiningWorkorderDetailItems($detailItems),
        ];
    }

    private function buildMobileMachiningWorkorderDetailItems(Collection $rows, ?int $restrictStepsToMachinistId = null): Collection
    {
        $items = collect();
        foreach ($rows as $row) {
            $allStepsFull = self::mobileMachiningStepsForRowUserAssignment($row)->sortBy('step_index')->values();
            $allSteps = $allStepsFull;
            if ($restrictStepsToMachinistId !== null && $restrictStepsToMachinistId > 0) {
                $allSteps = $allStepsFull
                    ->filter(static fn ($s) => (int) ($s->machinist_user_id ?? 0) === $restrictStepsToMachinistId)
                    ->values();
            }

            if ($allSteps->isEmpty()) {
                continue;
            }

            $fallbackMachinistId = (int) ($allStepsFull->first(static fn ($x) => (int) ($x->machinist_user_id ?? 0) > 0)?->machinist_user_id ?? 0);
            $parentForChain = $row->edit_machining_process ?? $row->bushing_batch ?? $row->bushing_process ?? null;
            $parentSendStart = $parentForChain?->date_start;
            $effectiveStartByStepIndex = [];
            $prevFinishForChain = null;
            foreach ($allStepsFull as $chainStep) {
                $idx = (int) $chainStep->step_index;
                $effectiveStartByStepIndex[$idx] = $idx === 1 ? ($chainStep->date_start ?? $parentSendStart) : $prevFinishForChain;
                $prevFinishForChain = $chainStep->date_finish;
            }

            foreach ($allSteps as $step) {
                $assignedId = (int) ($step->machinist_user_id ?? 0);
                $displayMachinistId = $assignedId > 0 ? $assignedId : ($fallbackMachinistId > 0 ? $fallbackMachinistId : 0);
                [$detailName, $detailLabel] = self::mobileMachiningStepDetailLabels($step, $row);
                $stepIndex = (int) $step->step_index;
                $items->push((object) [
                    'kind' => 'step',
                    'step' => $step,
                    'row' => $row,
                    'detail_name' => $detailName,
                    'detail_label' => $detailLabel,
                    'detail_serial' => self::mobileMachiningStepDetailSerial($step, $row),
                    'date_parent' => self::machiningStepDateParent($step),
                    'effective_step_start' => $effectiveStartByStepIndex[$stepIndex] ?? null,
                    'display_machinist_user_id' => $displayMachinistId > 0 ? $displayMachinistId : null,
                ]);
            }
        }

        return $items;
    }

    private function mobileMachiningProcessCatalogForWorkorder(Workorder $workorder): array
    {
        $machiningBoardPnIds = ProcessName::machiningMachiningEcMergeProcessNameIds();
        if ($machiningBoardPnIds === []) {
            return [];
        }

        $ids = [];
        foreach ($workorder->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                if (! in_array((int) ($tp->process_names_id ?? 0), $machiningBoardPnIds, true)) {
                    continue;
                }
                foreach (TdrProcess::normalizeStoredProcessIds($tp->processes) as $pid) {
                    if ($pid > 0) {
                        $ids[$pid] = true;
                    }
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        return Process::query()
            ->whereIn('id', array_keys($ids))
            ->whereIn('process_names_id', $machiningBoardPnIds)
            ->select(['id', 'process'])
            ->get()
            ->keyBy(static fn (Process $p): int => (int) $p->id)
            ->all();
    }

    private static function mobileMachiningProcessesLabelForRow(object $row, array $machiningCatalog): string
    {
        $tp = $row->edit_machining_process ?? null;
        if ($tp instanceof TdrProcess) {
            $parts = [];
            foreach (TdrProcess::normalizeStoredProcessIds($tp->processes) as $id) {
                $pr = $machiningCatalog[$id] ?? null;
                if ($pr !== null && trim((string) ($pr->process ?? '')) !== '') {
                    $parts[] = trim((string) $pr->process);
                }
            }

            return implode(' · ', $parts);
        }

        return '';
    }

    private static function mobileMachiningStepGroupId(object $item): ?string
    {
        if (($item->kind ?? '') !== 'step' || ! isset($item->row)) {
            return null;
        }
        $row = $item->row;
        $woId = (int) ($row->workorder->id ?? 0);
        if ($row->edit_machining_process ?? null) {
            return $woId . ':tp:' . (int) $row->edit_machining_process->id;
        }
        if ($row->bushing_batch ?? null) {
            return $woId . ':bb:' . (int) $row->bushing_batch->id;
        }
        if ($row->bushing_process ?? null) {
            return $woId . ':bp:' . (int) $row->bushing_process->id;
        }

        return null;
    }

    private function groupMobileMachiningWorkorderDetailItems(Collection $items): Collection
    {
        $out = collect();
        $buffer = [];
        $bufferGroup = null;

        foreach ($items as $item) {
            $gk = self::mobileMachiningStepGroupId($item);
            if ($gk === null) {
                $this->flushMobileMachiningStepGroupBuffer($buffer, $out);
                $buffer = [];
                $bufferGroup = null;
                $out->push($item);
                continue;
            }
            if ($bufferGroup !== null && $gk !== $bufferGroup) {
                $this->flushMobileMachiningStepGroupBuffer($buffer, $out);
                $buffer = [];
            }
            $bufferGroup = $gk;
            $buffer[] = $item;
        }
        $this->flushMobileMachiningStepGroupBuffer($buffer, $out);

        return $out;
    }

    private function flushMobileMachiningStepGroupBuffer(array $buffer, Collection $out): void
    {
        if ($buffer === []) {
            return;
        }
        if (count($buffer) === 1) {
            $out->push($buffer[0]);
            return;
        }
        $first = $buffer[0];
        $out->push((object) [
            'kind' => 'step_group',
            'steps' => collect($buffer),
            'row' => $first->row,
            'detail_name' => $first->detail_name,
            'detail_label' => $first->detail_label,
            'detail_serial' => $first->detail_serial,
            'date_parent' => $first->date_parent,
            'processes_label' => $first->processes_label ?? '',
        ]);
    }

    private static function mobileMachiningDatePresent(mixed $d): bool
    {
        if ($d === null) {
            return false;
        }
        if ($d instanceof \DateTimeInterface) {
            return true;
        }

        return trim((string) $d) !== '';
    }

    private static function mobileMachiningRowHasAssignedStepForUser(object $row, int $userId): bool
    {
        foreach (self::mobileMachiningStepsForRowUserAssignment($row) as $step) {
            if ((int) ($step->machinist_user_id ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    private static function mobileMachiningStepsForRow(object $row): Collection
    {
        $parent = $row->edit_machining_process ?? $row->bushing_batch ?? $row->bushing_process ?? null;
        if ($parent === null) {
            return collect();
        }
        if ($parent instanceof TdrProcess || $parent instanceof WoBushingBatch || $parent instanceof WoBushingProcess) {
            $parent->loadMissing('machiningWorkSteps');
            return $parent->machiningWorkSteps->values();
        }

        return collect();
    }

    private static function mobileMachiningStepsForRowUserAssignment(object $row): Collection
    {
        if (! empty($row->bushing_batch) && empty($row->bushing_process)) {
            return self::mobileMachiningStepsForBushingBatch($row->bushing_batch);
        }

        return self::mobileMachiningStepsForRow($row);
    }

    private static function mobileMachiningStepDetailSerial(MachiningWorkStep $step, object $row): string
    {
        if (! $step->tdr_process_id) {
            return '';
        }
        $tpid = (int) $step->tdr_process_id;
        $embTp = $row->edit_machining_process ?? null;
        $tdr = $embTp !== null && (int) $embTp->id === $tpid ? $embTp->loadMissing('tdr')->tdr : TdrProcess::query()->with(['tdr'])->find($tpid)?->tdr;
        if ($tdr === null) {
            return '';
        }
        foreach (['serial_number', 'assy_serial_number'] as $attr) {
            $v = trim((string) ($tdr->{$attr} ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    private static function mobileMachiningStepDetailLabels(MachiningWorkStep $step, object $fallbackRow): array
    {
        if ($step->tdr_process_id) {
            $tp = TdrProcess::query()->with(['tdr.component'])->find($step->tdr_process_id);
            if ($tp) {
                $c = $tp->tdr?->component;
                return [trim((string) ($c?->name ?? '')) ?: '—', trim((string) ($c?->part_number ?? ''))];
            }
        }
        if ($step->wo_bushing_process_id) {
            $wp = WoBushingProcess::query()->with(['line.component'])->find($step->wo_bushing_process_id);
            if ($wp) {
                $c = $wp->line?->component;
                return [trim((string) ($c?->name ?? '')) ?: 'Bushing', trim((string) ($c?->part_number ?? ''))];
            }
        }
        if ($step->wo_bushing_batch_id) {
            return ['Bushing · Batch', self::mobileMachiningBatchPartNumbersLabel((int) $step->wo_bushing_batch_id)];
        }

        return [(string) ($fallbackRow->detail_name ?? '—'), (string) ($fallbackRow->detail_label ?? '')];
    }

    private static function machiningStepDateParent(MachiningWorkStep $step): TdrProcess|WoBushingBatch|WoBushingProcess|null
    {
        if ($step->tdr_process_id) {
            return TdrProcess::query()->find($step->tdr_process_id);
        }
        if ($step->wo_bushing_batch_id) {
            return WoBushingBatch::query()->find($step->wo_bushing_batch_id);
        }
        if ($step->wo_bushing_process_id) {
            return WoBushingProcess::query()->find($step->wo_bushing_process_id);
        }

        return null;
    }

    private static function mobileMachiningBatchPartNumbersLabel(int $batchId): string
    {
        $batch = WoBushingBatch::query()->find($batchId);
        if ($batch === null) {
            return '—';
        }
        $batch->loadMissing(['woBushingProcesses.line.component']);
        $pns = $batch->woBushingProcesses
            ->map(static fn (WoBushingProcess $wp) => trim((string) ($wp->line?->component?->part_number ?? '')))
            ->filter()
            ->unique()
            ->values();

        return $pns->isNotEmpty() ? $pns->implode(', ') : '—';
    }

    private static function mobileMachiningStepsForBushingBatch(WoBushingBatch $batch): Collection
    {
        $batch->loadMissing(['machiningWorkSteps', 'woBushingProcesses.machiningWorkSteps']);
        $merged = $batch->machiningWorkSteps->values();
        foreach ($batch->woBushingProcesses as $proc) {
            $proc->loadMissing('machiningWorkSteps');
            $merged = $merged->concat($proc->machiningWorkSteps);
        }

        return $merged->unique('id')->values();
    }

    private function workorderMediaCount(Workorder $workorder, string $collection, ?string $mimePrefix = null): int
    {
        $query = $workorder->media()->where('collection_name', $collection);
        if ($mimePrefix !== null) {
            $query->where('mime_type', 'like', $mimePrefix . '%');
        }

        return (int) $query->count();
    }

    private function buildMachiningWorkorderDocPdfBinary(string $raw, string $mime): string
    {
        $normalized = $this->normalizeMachiningDocImageForPdf($raw, $mime);
        $embed = $normalized ?? ['data' => $raw, 'mime' => $mime];
        $data = $embed['data'];
        $mimeOut = (string) $embed['mime'];

        $info = @getimagesizefromstring($data);
        if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
            throw new \RuntimeException('Invalid image dimensions.');
        }

        $wPx = (int) $info[0];
        $hPx = (int) $info[1];
        $imgRatio = $wPx / $hPx;
        $marginMm = 5.0;
        $paperWmm = 8.5 * 25.4;
        $paperHmm = 11.0 * 25.4;
        $maxInnerW = $paperWmm - 2 * $marginMm;
        $maxInnerH = $paperHmm - 2 * $marginMm;
        $boxRatio = $maxInnerW / $maxInnerH;

        if ($imgRatio > $boxRatio) {
            $dispWmm = $maxInnerW;
            $dispHmm = $maxInnerW / $imgRatio;
        } else {
            $dispHmm = $maxInnerH;
            $dispWmm = $maxInnerH * $imgRatio;
        }

        $pageWmm = $dispWmm + 2 * $marginMm;
        $pageHmm = $dispHmm + 2 * $marginMm;
        $mm2pt = static fn (float $mm): float => $mm * 72 / 25.4;
        $pageWpt = $mm2pt($pageWmm);
        $pageHpt = $mm2pt($pageHmm);
        $src = 'data:' . $mimeOut . ';base64,' . base64_encode($data);
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<style>@page{margin:0;}html,body{margin:0;padding:0;}'
            . 'body{padding:' . $marginMm . 'mm;box-sizing:border-box;}'
            . 'img{display:block;margin:0;padding:0;width:' . $dispWmm . 'mm;height:' . $dispHmm . 'mm;}'
            . '</style></head><body><img src="'
            . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" alt=""></body></html>';

        return Pdf::loadHTML($html)
            ->setPaper([0.0, 0.0, $pageWpt, $pageHpt])
            ->output();
    }

    private function normalizeMachiningDocImageForPdf(string $raw, string $mime): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $im = @imagecreatefromstring($raw);
        if ($im === false) {
            return null;
        }

        if (function_exists('exif_read_data') && str_contains(strtolower($mime), 'jpeg')) {
            $tmp = tempnam(sys_get_temp_dir(), 'mdoc');
            if ($tmp !== false) {
                file_put_contents($tmp, $raw);
                $exif = @exif_read_data($tmp);
                @unlink($tmp);
                if (is_array($exif) && ! empty($exif['Orientation'])) {
                    $fixed = $this->applyExifOrientationToGdImage($im, (int) $exif['Orientation']);
                    if ($fixed !== false) {
                        imagedestroy($im);
                        $im = $fixed;
                    }
                }
            }
        }

        if (function_exists('imageistruecolor') && ! imageistruecolor($im)) {
            $w = imagesx($im);
            $h = imagesy($im);
            $tc = imagecreatetruecolor($w, $h);
            if ($tc !== false) {
                $white = imagecolorallocate($tc, 255, 255, 255);
                imagefill($tc, 0, 0, $white);
                imagecopy($tc, $im, 0, 0, 0, 0, $w, $h);
                imagedestroy($im);
                $im = $tc;
            }
        }

        ob_start();
        imagejpeg($im, null, 92);
        $out = (string) ob_get_clean();
        imagedestroy($im);

        if ($out === '') {
            return null;
        }

        return ['data' => $out, 'mime' => 'image/jpeg'];
    }

    private function applyExifOrientationToGdImage($im, int $orientation)
    {
        $rot = null;
        $flipH = false;
        $flipV = false;
        switch ($orientation) {
            case 2: $flipH = true; break;
            case 3: $rot = 180; break;
            case 4: $flipV = true; break;
            case 5: $flipH = true; $rot = 270; break;
            case 6: $rot = 270; break;
            case 7: $flipH = true; $rot = 90; break;
            case 8: $rot = 90; break;
            default: return $im;
        }
        if ($flipH && function_exists('imageflip')) {
            imageflip($im, IMG_FLIP_HORIZONTAL);
        }
        if ($flipV && function_exists('imageflip')) {
            imageflip($im, IMG_FLIP_VERTICAL);
        }
        if ($rot !== null) {
            $bg = imagecolorallocatealpha($im, 255, 255, 255, 127);
            $turned = imagerotate($im, $rot, $bg !== false ? $bg : 0);
            if ($turned === false) {
                return false;
            }
            imagedestroy($im);
            return $turned;
        }

        return $im;
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
