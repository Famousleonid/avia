<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $logName = (string)$request->get('log_name', 'all');
        $event = (string)$request->get('event', 'all');
        $subject = (string)$request->get('subject_type', 'all');
        $causerId = (string)$request->get('causer_id', 'all');
        $from = (string)$request->get('from', '');
        $to = (string)$request->get('to', '');
        $perPage = (int)$request->get('per_page', 50);
        $perPage = in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest('id');

        // -------------------------
        // Filters
        // -------------------------
        if ($logName !== 'all' && $logName !== '') {
            $query->where('log_name', $logName);
        }

        if ($event !== 'all' && $event !== '') {
            $query->where('event', $event);
        }

        if ($subject !== 'all' && $subject !== '') {
            $query->where('subject_type', $subject);
        }

        if ($causerId !== 'all' && $causerId !== '') {
            $query->where('causer_id', (int)$causerId);
        }

        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        // -------------------------
        // Search "по всем полям"
        // -------------------------
        if ($q !== '') {
            // защита от слишком длинных строк
            $q = Str::limit($q, 200, '');

            $query->where(function ($qq) use ($q) {
                $qq->where('log_name', 'like', "%{$q}%")
                    ->orWhere('event', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('subject_type', 'like', "%{$q}%")
                    ->orWhere('subject_id', 'like', "%{$q}%")
                    // properties (json/text) — самый полезный “поиск по всему”
                    ->orWhere('properties', 'like', "%{$q}%")
                    // causer name/email
                    ->orWhereHas('causer', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $activities = $query->paginate($perPage)->withQueryString();

        // -------------------------
        // Data for filter dropdowns
        // -------------------------
        $logNames = Activity::query()
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $subjectTypes = Activity::query()
            ->select('subject_type')
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $causers = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);


        return view('admin.log.index', compact('activities','logNames',  'subjectTypes', 'causers', 'perPage' ));
    }
}
