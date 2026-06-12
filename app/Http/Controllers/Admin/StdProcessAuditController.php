<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StdProcess;
use App\Services\StdProcessAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StdProcessAuditController extends Controller
{
    public function index(Request $request, StdProcessAuditService $auditService)
    {
        $std = (string) $request->query('std', '');
        if (! in_array($std, StdProcess::validStdValues(), true)) {
            $std = '';
        }

        $search = trim((string) $request->query('q', ''));
        $conflicts = $auditService->conflicts(null, $std !== '' ? $std : null);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $conflicts = $conflicts
                ->filter(function (array $conflict) use ($needle): bool {
                    $haystack = collect([
                        $conflict['manual_number'] ?? '',
                        $conflict['manual_title'] ?? '',
                        $conflict['std'] ?? '',
                        $conflict['base_ipl'] ?? '',
                        implode(' ', $conflict['processes'] ?? []),
                    ]);

                    foreach ($conflict['rows'] ?? [] as $row) {
                        $haystack->push($row['ipl_num'] ?? '');
                        $haystack->push($row['part_number'] ?? '');
                        $haystack->push($row['description'] ?? '');
                        $haystack->push($row['process'] ?? '');
                    }

                    return str_contains(mb_strtolower($haystack->implode(' ')), $needle);
                })
                ->values();
        }

        $totalsByStd = $conflicts
            ->groupBy('std')
            ->map(fn (Collection $rows): int => $rows->count())
            ->all();

        return view('admin.std_process_audit.index', [
            'conflicts' => $conflicts,
            'std' => $std,
            'search' => $search,
            'stdLabels' => [
                StdProcess::STD_NDT => 'NDT',
                StdProcess::STD_CAD => 'CAD',
                StdProcess::STD_STRESS => 'Stress',
                StdProcess::STD_PAINT => 'Paint',
            ],
            'totalsByStd' => $totalsByStd,
        ]);
    }
}
