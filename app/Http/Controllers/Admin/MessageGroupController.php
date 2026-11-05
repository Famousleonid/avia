<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessageGroupController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()->canManageGroupMessages(), 403);
        return response()->json([
            'groups' => MessageGroup::with('users:id')->orderBy('name')->get()->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'user_ids' => $group->users->pluck('id'),
            ]),
            'can_manage' => true,
            'users' => User::withoutReviewAccounts()->get(['id', 'name', 'selection_name_order'])
                ->sortBy(fn ($user) => mb_strtolower($user->selection_name))->values()
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->selection_name]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, new MessageGroup());
    }

    public function update(Request $request, MessageGroup $messageGroup): JsonResponse
    {
        return $this->save($request, $messageGroup);
    }

    private function save(Request $request, MessageGroup $group): JsonResponse
    {
        abort_unless($request->user()->canManageGroupMessages(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('message_groups')->ignore($group->id)],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
        $ids = User::withoutReviewAccounts()->whereIn('id', $data['user_ids'])->pluck('id');
        abort_if($ids->isEmpty(), 422, 'Select at least one user.');
        DB::transaction(function () use ($data, $group, $ids) {
            $group->fill(['name' => $data['name']])->save();
            $group->users()->sync($ids);
        });
        return response()->json(['ok' => true, 'id' => $group->id]);
    }

    public function destroy(Request $request, MessageGroup $messageGroup): JsonResponse
    {
        abort_unless($request->user()->canManageGroupMessages(), 403);
        $messageGroup->delete();
        return response()->json(['ok' => true]);
    }
}
