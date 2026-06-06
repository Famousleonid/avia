<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.project_settings.index', [
            'qrEnabled' => ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        ProjectSetting::setBoolean(
            ProjectSetting::PRINT_FORMS_QR_ENABLED,
            $request->boolean('print_forms_qr_enabled')
        );

        return redirect()
            ->route('admin.project-settings.index')
            ->with('success', 'Project settings saved.');
    }
}
