<?php

namespace App\Console\Commands;

use App\Models\CustomerInteractionNote;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class SendMarketingFollowUpNotifications extends Command
{
    protected $signature = 'marketing:send-follow-ups {--dry-run : Show due reminders without sending notifications}';

    protected $description = 'Send due marketing customer follow-up notifications.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $notes = CustomerInteractionNote::query()
            ->with(['customer', 'contact', 'user'])
            ->where('follow_up_status', CustomerInteractionNote::STATUS_OPEN)
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '<=', now()->toDateString())
            ->whereNull('reminder_sent_at')
            ->orderBy('follow_up_at')
            ->orderBy('id')
            ->get();

        if ($notes->isEmpty()) {
            $this->info('No due marketing follow-ups.');
            return self::SUCCESS;
        }

        $marketingUsers = User::query()
            ->with(['role', 'featureAccesses'])
            ->get()
            ->filter(fn (User $user) => Gate::forUser($user)->allows('feature.marketing'))
            ->values();

        foreach ($notes as $note) {
            $customerName = (string) ($note->customer?->name ?? 'Customer');
            $contactName = trim((string) ($note->contact?->full_name ?? ''));
            $date = format_project_date($note->follow_up_at) ?? (string) $note->follow_up_at;
            $text = trim("Follow up with {$customerName}" . ($contactName !== '' ? " / {$contactName}" : '') . " on {$date}.");

            $this->line($text);

            if ($dryRun) {
                continue;
            }

            $recipients = collect([$note->user])
                ->filter(fn (?User $user): bool => $user !== null && Gate::forUser($user)->allows('feature.marketing'))
                ->merge($marketingUsers)
                ->filter(fn (User $user) => empty(($user->notification_prefs ?? [])['mute_all']))
                ->unique('id')
                ->values();

            if ($recipients->isEmpty()) {
                $this->warn("No recipients found for marketing follow-up note {$note->id}.");
                continue;
            }

            foreach ($recipients as $recipient) {
                $recipient->notify(new NewMessageNotification(
                    fromUserId: 0,
                    fromName: 'Marketing',
                    text: $text,
                    url: route('marketing.index', ['customer' => $note->customer_id]),
                    type: 'marketing',
                    event: 'follow_up_due',
                    ui: [
                        'customer' => [
                            'id' => $note->customer_id,
                            'name' => $customerName,
                        ],
                        'contact' => [
                            'id' => $note->contact_id,
                            'name' => $contactName,
                        ],
                        'dates' => [
                            'follow_up_at' => $date,
                        ],
                    ],
                    severity: 'warning',
                    title: 'Marketing follow-up',
                    payload: [
                        'customer_id' => $note->customer_id,
                        'note_id' => $note->id,
                    ],
                ));
            }

            $note->forceFill(['reminder_sent_at' => now()])->save();
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Marketing follow-up notifications sent.');

        return self::SUCCESS;
    }
}
