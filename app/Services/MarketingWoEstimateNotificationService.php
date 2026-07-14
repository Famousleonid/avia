<?php

namespace App\Services;

use App\Mail\MarketingWoEstimateDateMail;
use App\Models\MarketingWoEstimateNotification;
use App\Models\ProjectSetting;
use App\Models\Workorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

class MarketingWoEstimateNotificationService
{
    public function handleEstimateDateChange(Workorder $workorder, ?string $oldDate, ?string $newDate): void
    {
        if ($newDate === null) {
            MarketingWoEstimateNotification::query()
                ->where('workorder_id', $workorder->id)
                ->whereNull('sent_at')
                ->delete();

            return;
        }

        if ($oldDate === $newDate) {
            return;
        }

        $delayDays = ProjectSetting::marketingWoEstimateEmailDelayDays();
        $pending = MarketingWoEstimateNotification::query()
            ->where('workorder_id', $workorder->id)
            ->whereNull('sent_at')
            ->latest('id')
            ->first();

        if ($oldDate !== null && ! $pending) {
            return;
        }

        $attributes = [
            'customer_id' => $workorder->customer_id,
            'estimate_date' => $newDate,
            'triggered_at' => now(),
            'due_at' => $this->dueAt($newDate, $delayDays),
            'mail_error' => null,
        ];

        if ($pending) {
            $pending->forceFill($attributes)->save();
            return;
        }

        MarketingWoEstimateNotification::query()->create([
            'workorder_id' => $workorder->id,
            ...$attributes,
        ]);
    }

    public function reschedulePending(int $delayDays): void
    {
        MarketingWoEstimateNotification::query()
            ->whereNull('sent_at')
            ->orderBy('id')
            ->chunkById(200, function ($notifications) use ($delayDays): void {
                foreach ($notifications as $notification) {
                    $notification->forceFill([
                        'due_at' => $this->dueAt($notification->estimate_date->toDateString(), $delayDays),
                    ])->save();
                }
            });
    }

    public function sendDue(bool $dryRun = false): int
    {
        $recipients = ProjectSetting::marketingWoEstimateEmailRecipients();
        if ($recipients === []) {
            return 0;
        }

        $notifications = MarketingWoEstimateNotification::query()
            ->with([
                'customer.marketingProfile.companyType',
                'customer.marketingProfile.segment',
                'customer.marketingProfile.owner',
                'customer.marketingProfile.countryRef',
                'workorder.unit.manual.plane',
                'workorder.instruction',
                'workorder.main.task',
            ])
            ->whereNull('sent_at')
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        $sent = 0;

        foreach ($notifications as $notification) {
            if (! $notification->workorder || ! $this->isWaitingApproval($notification->workorder)) {
                if (! $dryRun) {
                    $notification->delete();
                }

                continue;
            }

            if ($dryRun) {
                $sent++;
                continue;
            }

            try {
                foreach ($recipients as $email) {
                    Mail::to($email)->send(new MarketingWoEstimateDateMail($notification));
                }

                $notification->forceFill([
                    'sent_at' => now(),
                    'recipients' => $recipients,
                    'mail_error' => null,
                ])->save();

                $sent++;
            } catch (\Throwable $e) {
                $notification->forceFill([
                    'mail_error' => $e->getMessage(),
                ])->save();
            }
        }

        return $sent;
    }

    private function dueAt(string $estimateDate, int $delayDays): CarbonImmutable
    {
        return CarbonImmutable::parse($estimateDate, config('app.timezone'))
            ->startOfDay()
            ->addDays(max(0, min(365, $delayDays)));
    }

    private function isWaitingApproval(Workorder $workorder): bool
    {
        return $workorder->approve_at === null && ! $workorder->isDone();
    }
}
