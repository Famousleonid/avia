<?php

namespace App\Mail;

use App\Models\MarketingWoFile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketingWoFileMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MarketingWoFile $marketingFile)
    {
    }

    public function build(): self
    {
        $this->marketingFile->loadMissing(['workorder.customer', 'uploader']);
        $workorder = $this->marketingFile->workorder;

        return $this
            ->subject('W' . $workorder->number . ' — New ' . $this->marketingFile->categoryLabel() . ' file')
            ->view('emails.marketing.wo-file-shared')
            ->with([
                'file' => $this->marketingFile,
                'workorder' => $workorder,
                'customer' => $workorder->customer,
                'openUrl' => route('marketing.index', [
                    'customer' => $workorder->customer_id,
                    'tab' => 'workorders',
                    'wo' => 'W' . $workorder->number,
                    'files' => 1,
                ]),
            ]);
    }
}
