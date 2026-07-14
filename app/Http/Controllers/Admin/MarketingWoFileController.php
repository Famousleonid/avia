<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingWoFile;
use App\Models\MarketingWoFileRead;
use App\Models\User;
use App\Models\Workorder;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MarketingWoFileController extends Controller
{
    public function index(Workorder $workorder): JsonResponse
    {
        abort_if($workorder->is_draft, 404);

        $files = $workorder->marketingFiles()
            ->with(['media', 'uploader', 'recipients.user'])
            ->latest()
            ->get();

        $this->markRead($files->pluck('id')->all(), (int) auth()->id());

        return response()->json($this->workspacePayload($workorder, $files));
    }

    public function store(Request $request, Workorder $workorder): JsonResponse
    {
        abort_if($workorder->is_draft, 404);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
            'category' => ['required', Rule::in(array_keys(MarketingWoFile::CATEGORIES))],
            'display_name' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'recipient_ids' => ['nullable', 'array', 'max:50'],
            'recipient_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'send_email' => ['nullable', 'boolean'],
            'version_of_id' => ['nullable', 'integer', 'exists:marketing_wo_files,id'],
        ]);

        $uploads = $request->file('files', []);
        if (! is_array($uploads)) {
            $uploads = [$uploads];
        }

        if (! empty($data['version_of_id']) && count($uploads) !== 1) {
            throw ValidationException::withMessages([
                'files' => 'Upload one file when adding a new version.',
            ]);
        }

        $versionOf = null;
        if (! empty($data['version_of_id'])) {
            $versionOf = MarketingWoFile::query()->findOrFail((int) $data['version_of_id']);
            abort_unless((int) $versionOf->workorder_id === (int) $workorder->id, 422, 'Version file does not belong to this workorder.');
        }

        $recipientIds = collect($data['recipient_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $recipients = User::query()
            ->with(['role', 'featureAccesses'])
            ->whereIn('id', $recipientIds)
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $user): bool => Gate::forUser($user)->allows('feature.marketing'))
            ->values();

        if ($recipients->count() !== $recipientIds->count()) {
            throw ValidationException::withMessages([
                'recipient_ids' => 'Select only active users with Marketing access and an email address.',
            ]);
        }

        $sendEmail = (bool) ($data['send_email'] ?? false);
        if ($sendEmail && $recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'recipient_ids' => 'Select at least one recipient before enabling email notification.',
            ]);
        }

        $createdFiles = collect();

        try {
            foreach ($uploads as $upload) {
                $extension = strtolower((string) $upload->getClientOriginalExtension());
                $storedName = 'wo_' . $workorder->number . '_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(6));
                if ($extension !== '') {
                    $storedName .= '.' . $extension;
                }

                $media = $workorder->addMedia($upload)
                    ->usingFileName($storedName)
                    ->withCustomProperties(['original_file_name' => $upload->getClientOriginalName()])
                    ->toMediaCollection(MarketingWoFile::COLLECTION, 'private');

                try {
                    $marketingFile = DB::transaction(function () use ($workorder, $media, $upload, $data, $versionOf, $recipients, $sendEmail, $uploads) {
                        $isSingleUpload = count($uploads) === 1;
                        $displayName = $isSingleUpload ? trim((string) ($data['display_name'] ?? '')) : '';
                        $displayName = $displayName !== '' ? $displayName : $upload->getClientOriginalName();

                        $versionGroup = $versionOf?->version_group ?: (string) Str::uuid();
                        $versionNumber = $versionOf
                            ? ((int) MarketingWoFile::withTrashed()->where('version_group', $versionGroup)->max('version_number') + 1)
                            : 1;

                        $file = MarketingWoFile::query()->create([
                            'workorder_id' => $workorder->id,
                            'media_id' => $media->id,
                            'uploaded_by_user_id' => auth()->id(),
                            'category' => $versionOf?->category ?: $data['category'],
                            'display_name' => $displayName,
                            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
                            'version_group' => $versionGroup,
                            'version_number' => $versionNumber,
                        ]);

                        foreach ($recipients as $recipient) {
                            $file->recipients()->create([
                                'user_id' => $recipient->id,
                                'email_requested' => $sendEmail,
                                'email_next_attempt_at' => $sendEmail ? now() : null,
                            ]);
                        }

                        return $file;
                    });
                } catch (\Throwable $exception) {
                    $media->delete();
                    throw $exception;
                }

                $createdFiles->push($marketingFile);
            }
        } catch (\Throwable $exception) {
            foreach ($createdFiles as $createdFile) {
                $createdFile->media?->delete();
                $createdFile->forceDelete();
            }
            throw $exception;
        }

        foreach ($createdFiles as $createdFile) {
            $this->sendDatabaseNotifications($createdFile, $recipients);
            $this->logFileActivity('created', $createdFile, 'Marketing WO file uploaded');
        }

        $files = $workorder->marketingFiles()
            ->with(['media', 'uploader', 'recipients.user'])
            ->latest()
            ->get();
        $this->markRead($files->pluck('id')->all(), (int) auth()->id());

        return response()->json($this->workspacePayload($workorder, $files), 201);
    }

    public function preview(Workorder $workorder, MarketingWoFile $marketingWoFile): BinaryFileResponse
    {
        $this->ensureBelongsToWorkorder($workorder, $marketingWoFile);
        $media = $marketingWoFile->media;
        abort_unless($media && $this->isPreviewable((string) $media->mime_type), 404);
        abort_unless(is_file($media->getPath()), 404);

        return response()->file($media->getPath(), ['Content-Type' => $media->mime_type]);
    }

    public function download(Workorder $workorder, MarketingWoFile $marketingWoFile): BinaryFileResponse
    {
        $this->ensureBelongsToWorkorder($workorder, $marketingWoFile);
        $media = $marketingWoFile->media;
        abort_unless($media && is_file($media->getPath()), 404);

        return response()->download($media->getPath(), $marketingWoFile->display_name);
    }

    public function destroy(Workorder $workorder, MarketingWoFile $marketingWoFile): JsonResponse
    {
        $this->ensureBelongsToWorkorder($workorder, $marketingWoFile);
        $user = auth()->user();
        abort_unless($user && ((int) $marketingWoFile->uploaded_by_user_id === (int) $user->id || $user->isSystemAdmin()), 403);

        $this->logFileActivity('deleted', $marketingWoFile, 'Marketing WO file deleted');
        $marketingWoFile->delete();

        return response()->json(['ok' => true]);
    }

    private function workspacePayload(Workorder $workorder, $files): array
    {
        $workorder->loadMissing('media');
        $productionMedia = $workorder->media->reject(fn ($media) => $media->collection_name === MarketingWoFile::COLLECTION);

        return [
            'workorder' => [
                'id' => $workorder->id,
                'number_label' => 'W' . $workorder->number,
            ],
            'categories' => MarketingWoFile::CATEGORIES,
            'manager_files' => $files->map(fn (MarketingWoFile $file) => $this->filePayload($workorder, $file))->values(),
            'summary' => [
                'manager_count' => $files->count(),
                'unread_count' => 0,
                'production_image_count' => $productionMedia->filter(fn ($media) => str_starts_with((string) $media->mime_type, 'image/'))->count(),
                'production_pdf_count' => $productionMedia->filter(fn ($media) => str_contains((string) $media->mime_type, 'pdf'))->count(),
            ],
            'production' => [
                'photos_url' => route('workorders.photos', $workorder->id),
                'pdfs_url' => route('workorders.pdfs', $workorder->id),
            ],
            'urls' => [
                'upload' => route('marketing.workorders.files.store', $workorder),
            ],
        ];
    }

    private function filePayload(Workorder $workorder, MarketingWoFile $file): array
    {
        $media = $file->media;
        $emailRecipients = $file->recipients->where('email_requested', true);
        $sentCount = $emailRecipients->whereNotNull('email_sent_at')->count();
        $pendingCount = $emailRecipients->whereNull('email_sent_at')->count();
        $allRecipientNames = $file->recipients->map(fn ($recipient) => $recipient->user?->selection_name)->filter()->values();

        $notificationLabel = 'Not sent';
        if ($allRecipientNames->isNotEmpty() && $emailRecipients->isEmpty()) {
            $notificationLabel = 'In-app only';
        } elseif ($sentCount > 0 && $pendingCount === 0) {
            $notificationLabel = 'Email sent to ' . $sentCount;
        } elseif ($pendingCount > 0) {
            $notificationLabel = 'Email pending for ' . $pendingCount;
        }

        return [
            'id' => $file->id,
            'display_name' => $file->display_name,
            'original_name' => (string) ($media?->getCustomProperty('original_file_name') ?: $media?->file_name),
            'category' => $file->category,
            'category_label' => $file->categoryLabel(),
            'comment' => (string) ($file->comment ?? ''),
            'uploader_name' => $file->uploader?->selection_name ?: 'System',
            'uploaded_at' => trim((string) format_project_date($file->created_at) . ' ' . $file->created_at?->format('H:i')),
            'version_number' => $file->version_number,
            'size_label' => $this->fileSizeLabel((int) ($media?->size ?? 0)),
            'mime_type' => (string) ($media?->mime_type ?? ''),
            'is_previewable' => $media ? $this->isPreviewable((string) $media->mime_type) : false,
            'recipients' => $allRecipientNames,
            'notification_label' => $notificationLabel,
            'can_delete' => (int) $file->uploaded_by_user_id === (int) auth()->id() || auth()->user()?->isSystemAdmin(),
            'urls' => [
                'preview' => route('marketing.workorders.files.preview', [$workorder, $file]),
                'download' => route('marketing.workorders.files.download', [$workorder, $file]),
                'delete' => route('marketing.workorders.files.destroy', [$workorder, $file]),
            ],
        ];
    }

    private function sendDatabaseNotifications(MarketingWoFile $file, $recipients): void
    {
        $file->loadMissing(['workorder.customer', 'uploader']);
        $workorder = $file->workorder;
        $actor = $file->uploader;
        $url = route('marketing.index', [
            'customer' => $workorder->customer_id,
            'tab' => 'workorders',
            'wo' => 'W' . $workorder->number,
            'files' => 1,
        ]);

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new NewMessageNotification(
                    fromUserId: (int) ($actor?->id ?? 0),
                    fromName: $actor?->selection_name ?: 'System',
                    text: 'W' . $workorder->number . ': ' . $file->display_name . ' was added to Marketing Files.',
                    url: $url,
                    type: 'marketing_file',
                    event: 'uploaded',
                    ui: ['workorder_id' => $workorder->id, 'marketing_wo_file_id' => $file->id],
                    severity: 'info',
                    title: 'New Marketing WO file',
                ));
                $file->recipients()->where('user_id', $recipient->id)->update(['notified_at' => now()]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    private function markRead(array $fileIds, int $userId): void
    {
        if ($userId <= 0 || $fileIds === []) {
            return;
        }

        foreach ($fileIds as $fileId) {
            MarketingWoFileRead::query()->updateOrCreate(
                ['marketing_wo_file_id' => $fileId, 'user_id' => $userId],
                ['read_at' => now()]
            );
        }
    }

    private function ensureBelongsToWorkorder(Workorder $workorder, MarketingWoFile $file): void
    {
        abort_unless((int) $file->workorder_id === (int) $workorder->id, 404);
    }

    private function isPreviewable(string $mime): bool
    {
        return str_starts_with($mime, 'image/')
            || $mime === 'application/pdf'
            || str_starts_with($mime, 'text/');
    }

    private function fileSizeLabel(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }

    private function logFileActivity(string $event, MarketingWoFile $file, string $description): void
    {
        $file->loadMissing(['workorder.customer', 'uploader']);
        activity('marketing')
            ->causedBy(auth()->user())
            ->performedOn($file)
            ->event($event)
            ->withProperties([
                'customer' => (string) ($file->workorder->customer?->name ?? ''),
                'customer_id' => $file->workorder->customer_id,
                'workorder' => 'W' . $file->workorder->number,
                'file' => $file->display_name,
                'category' => $file->categoryLabel(),
                'version' => $file->version_number,
            ])
            ->log($description);
    }
}
