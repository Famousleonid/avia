<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualInProcessCheckSheet;
use App\Models\Workorder;
use Illuminate\Support\Facades\Schema;

class InProcessCheckSheetController extends Controller
{
    public function show(Workorder $workorder)
    {
        $workorder->loadMissing('unit.manual.builder');
        $manual = $workorder->unit?->manual;
        // No write on GET, no shared default or silent fallback to another manual.
        $template = $manual && Schema::hasTable('manual_in_process_check_sheets')
            ? ManualInProcessCheckSheet::where('manual_id', $manual->id)->first()
            : null;
        $content = $template?->content;
        $available = $template && $template->schema_version === 1
            && is_array($content) && ! empty($content['rows']) && empty($content['issues']);

        return view('admin.tdrs.in-process-check-sheet', compact('workorder', 'manual', 'template', 'content', 'available'));
    }
}
