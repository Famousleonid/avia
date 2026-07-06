<?php

namespace App\Services;

use App\Mail\MarketingWoEstimateDateMail;
use App\Models\MarketingWoEstimateNotification;
use App\Models\ProjectSetting;
use App\Models\Workorder;
use Illuminate\Support\Facades\Mail;

class MarketingWoEstimateNotificationService
{
    public function handleEstimateDateChange(Workorder $workorder, ?string $oldDate, ?string $newDate): void
    {
        if ($oldDate !== null && $newDate === null) {
            MarketingWoEstimateNotification::query()
                ->where('workorder_id', $workorder->id)
                ->whereNull('sent_at')
                ->delete();

            return;
        }

        if ($oldDate !== null || $newDate === null) {
            return;
        }

        $delayDays = ProjectSetting::marketingWoEstimateEmailDelayDays();

        MarketingWoEstimateNotification::query()->create([
            'workorder_id' => $workorder->id,
            'customer_id' => $workorder->customer_id,
            'estimate_date' => $newDate,
            'triggered_at' => now(),
            'due_at' => now()->addDays($delayDays),
        ]);
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
}
