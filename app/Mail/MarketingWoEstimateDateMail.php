<?php

namespace App\Mail;

use App\Models\MarketingWoEstimateNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketingWoEstimateDateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public MarketingWoEstimateNotification $notification)
    {
    }

    public function build(): self
    {
        $workorder = $this->notification->workorder;
        $customer = $this->notification->customer ?? $workorder?->customer;
        $woLabel = $workorder?->number ? 'W' . $workorder->number : 'Workorder';
        $customerName = trim((string) ($customer?->name ?? 'Customer'));

        return $this
            ->subject("WO Estimate Date set for {$woLabel} / {$customerName}")
            ->view('emails.marketing.wo_estimate_date')
            ->with([
                'notification' => $this->notification,
                'workorder' => $workorder,
                'customer' => $customer,
            ]);
    }
}
