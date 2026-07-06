<?php

namespace App\Console\Commands;

use App\Models\ProjectSetting;
use App\Services\MarketingWoEstimateNotificationService;
use Illuminate\Console\Command;

class SendMarketingWoEstimateDateEmails extends Command
{
    protected $signature = 'marketing:send-wo-estimate-date-emails {--dry-run : Show due emails without sending}';

    protected $description = 'Send due marketing WO Estimate Date emails.';

    public function handle(MarketingWoEstimateNotificationService $service): int
    {
        $recipients = ProjectSetting::marketingWoEstimateEmailRecipients();
        if ($recipients === []) {
            $this->warn('No marketing WO Estimate Date email recipients configured.');
            return self::SUCCESS;
        }

        $sent = $service->sendDue((bool) $this->option('dry-run'));

        $this->info($this->option('dry-run')
            ? "Dry run complete. {$sent} WO Estimate Date email(s) due."
            : "Marketing WO Estimate Date emails sent: {$sent}.");

        return self::SUCCESS;
    }
}
