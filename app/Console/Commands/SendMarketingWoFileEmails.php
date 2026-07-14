<?php

namespace App\Console\Commands;

use App\Mail\MarketingWoFileMail;
use App\Models\MarketingWoFileRecipient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMarketingWoFileEmails extends Command
{
    protected $signature = 'marketing:send-wo-file-emails {--dry-run : Show due emails without sending}';

    protected $description = 'Send pending Marketing WO file email notifications.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;

        MarketingWoFileRecipient::query()
            ->where('email_requested', true)
            ->whereNull('email_sent_at')
            ->whereHas('file')
            ->whereHas('user', fn ($query) => $query->whereNotNull('email'))
            ->where(function ($query): void {
                $query->whereNull('email_next_attempt_at')
                    ->orWhere('email_next_attempt_at', '<=', now());
            })
            ->with(['file.workorder.customer', 'file.uploader', 'user'])
            ->orderBy('id')
            ->chunkById(100, function ($recipients) use ($dryRun, &$processed): void {
                foreach ($recipients as $recipient) {
                    $processed++;
                    if ($dryRun) {
                        $this->line('W' . $recipient->file->workorder->number . ' -> ' . $recipient->user->email);
                        continue;
                    }

                    try {
                        Mail::to($recipient->user->email)->send(new MarketingWoFileMail($recipient->file));
                        $recipient->forceFill([
                            'email_sent_at' => now(),
                            'email_next_attempt_at' => null,
                            'email_error' => null,
                            'email_attempts' => $recipient->email_attempts + 1,
                        ])->save();
                    } catch (\Throwable $exception) {
                        $attempts = $recipient->email_attempts + 1;
                        $recipient->forceFill([
                            'email_attempts' => $attempts,
                            'email_next_attempt_at' => now()->addMinutes(min(60, $attempts * 10)),
                            'email_error' => mb_substr($exception->getMessage(), 0, 4000),
                        ])->save();
                        report($exception);
                    }
                }
            });

        $this->info(($dryRun ? 'Due' : 'Processed') . " Marketing WO file email(s): {$processed}.");

        return self::SUCCESS;
    }
}
