<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use App\Services\MarketingWoEstimateNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectSettingController extends Controller
{
    public function index(): View
    {
        $marketingEmails = ProjectSetting::marketingWoEstimateEmailRecipients();

        return view('admin.project_settings.index', [
            'qrEnabled' => ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true),
            'marketingWoEstimateEmailRecipientsText' => implode("\n", $marketingEmails),
            'marketingWoEstimateEmailDelayDays' => ProjectSetting::marketingWoEstimateEmailDelayDays(),
        ]);
    }

    public function update(
        Request $request,
        MarketingWoEstimateNotificationService $estimateNotifications
    ): RedirectResponse
    {
        $data = $request->validate([
            'marketing_wo_estimate_email_recipients' => ['nullable', 'string', 'max:4000'],
            'marketing_wo_estimate_email_delay_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $emails = $this->parseEmailList((string) ($data['marketing_wo_estimate_email_recipients'] ?? ''));
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return back()
                    ->withErrors(['marketing_wo_estimate_email_recipients' => "Invalid email: {$email}"])
                    ->withInput();
            }
        }

        ProjectSetting::setBoolean(
            ProjectSetting::PRINT_FORMS_QR_ENABLED,
            $request->boolean('print_forms_qr_enabled')
        );

        $delayDays = (int) ($data['marketing_wo_estimate_email_delay_days']
            ?? ProjectSetting::marketingWoEstimateEmailDelayDays());

        ProjectSetting::setMarketingWoEstimateEmailSettings($emails, $delayDays);
        $estimateNotifications->reschedulePending($delayDays);

        return redirect()
            ->route('admin.project-settings.index')
            ->with('success', 'Project settings saved.');
    }

    /**
     * @return list<string>
     */
    private function parseEmailList(string $value): array
    {
        return collect(preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn ($email): string => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
