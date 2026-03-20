<?php

namespace App\Services\Ai\Tools;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Carbon;

class CreateWorkorderNoteTool
{
    public function run(User $user, array $args): array
    {
        $workorderId = (int)($args['workorder_id'] ?? 0);
        $text = trim((string)($args['text'] ?? ''));

        if ($workorderId <= 0 || $text === '') {
            return [
                'ok' => false,
                'message' => 'Invalid note data.',
            ];
        }

        $workorder = Workorder::withDrafts()->find($workorderId);

        if (! $workorder) {
            return [
                'ok' => false,
                'message' => 'Workorder not found.',
            ];
        }

        if (! $user->can('workorders.update', $workorder)) {
            return [
                'ok' => false,
                'message' => 'You do not have permission to edit this workorder.',
            ];
        }

        return [
            'ok' => false,
            'requires_confirmation' => true,
            'message' => "I prepared a note for WO #{$workorder->number}. Please confirm creation.",
            'action' => [
                'type' => 'create_workorder_note',
                'payload' => [
                    'workorder_id' => $workorder->id,
                    'text' => $text,
                ],
            ],
        ];
    }

    public function executeConfirmed(User $user, array $payload): array
    {
        $workorderId = (int)($payload['workorder_id'] ?? 0);
        $text = trim((string)($payload['text'] ?? ''));

        if ($workorderId <= 0 || $text === '') {
            return ['ok' => false, 'message' => 'Invalid confirmation payload.'];
        }

        $workorder = Workorder::withDrafts()->find($workorderId);
        if (! $workorder) {
            return ['ok' => false, 'message' => 'Workorder not found.'];
        }

        if (! $user->can('workorders.update', $workorder)) {
            return ['ok' => false, 'message' => 'You do not have permission to edit this workorder.'];
        }

        $old = (string)($workorder->notes ?? '');
        $stamp = Carbon::now()->format('Y-m-d H:i');
        $line = "[AI {$stamp} by {$user->name}] {$text}";
        $new = trim($old) === '' ? $line : ($old . PHP_EOL . PHP_EOL . $line);

        $workorder->notes = $new;
        $workorder->save();

        activity()
            ->useLog('workorders')
            ->performedOn($workorder)
            ->causedBy($user)
            ->withProperties([
                'old' => ['notes' => $old],
                'new' => ['notes' => $new],
            ])
            ->log($old === '' ? 'workorder_notes_created' : 'workorder_notes_updated');

        return [
            'ok' => true,
            'message' => "Note created for WO #{$workorder->number}.",
            'workorder' => [
                'id' => $workorder->id,
                'number' => $workorder->number,
            ],
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'createWorkorderNote',
            'description' => 'Prepare note for a workorder. Requires explicit UI confirmation before write.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'workorder_id' => [
                        'type' => 'integer',
                    ],
                    'text' => [
                        'type' => 'string',
                    ],
                ],
                'required' => ['workorder_id', 'text'],
                'additionalProperties' => false,
            ],
        ];
    }
}
