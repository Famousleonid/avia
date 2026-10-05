<?php

namespace App\Services;

use App\Models\ProcessName;
use App\Models\ProjectSetting;

class PrintFormQrSettings
{
    public const KEY = 'print_forms.qr_forms';

    public function catalog(): array
    {
        $forms = config('print_form_qr', []);
        foreach (ProcessName::orderBy('name')->get(['id', 'name']) as $process) {
            $forms['process_'.$process->id] = ['label' => 'Process: '.$process->name, 'default' => true];
        }
        return $forms;
    }

    public function selections(): array
    {
        $saved = ProjectSetting::valueFor(self::KEY);
        $result = [];
        foreach ($this->catalog() as $key => $form) {
            $result[$key] = (bool) ($saved[$key] ?? $form['default']);
        }
        return $result;
    }

    public function enabled(string $key): bool
    {
        if (!ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true)) return false;
        $saved = ProjectSetting::valueFor(self::KEY);
        $default = str_starts_with($key, 'process_') ? true : config('print_form_qr.'.$key.'.default', false);
        return (bool) ($saved[$key] ?? $default);
    }
}
