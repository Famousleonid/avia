<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PageVisitStatController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = PageVisit::query()
            ->with('user:id,name,email')
            ->when(
                $filters['user_id'] ?? null,
                fn ($query, $userId) => $query->where('user_id', $userId),
                fn ($query) => $query->whereHas('user', fn ($query) => $query->where('is_admin', false))
            )
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('visited_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('visited_at', '<=', Carbon::parse($to)->endOfDay()))
            ->latest('visited_at')
            ->limit(5000);

        $visits = $query->get();

        $rows = $visits
            ->groupBy(fn (PageVisit $visit) => ($visit->user_id ?: 'unknown') . '|' . $visit->visited_at->toDateString())
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'user' => $first->user,
                    'date' => $first->visited_at->toDateString(),
                    'latest_visit_at' => $group->max('visited_at'),
                    'visits_count' => $group->count(),
                    'pages' => $group
                        ->sortBy('visited_at')
                        ->map(fn (PageVisit $visit) => (object) [
                            'time' => $visit->visited_at->format('H:i:s'),
                            'path' => $visit->path,
                            'url' => $visit->url,
                            'route_name' => $visit->route_name,
                        ])
                        ->values(),
                ];
            })
            ->sortByDesc('latest_visit_at')
            ->values();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.stats.page-visits', [
            'rows' => $rows,
            'users' => $users,
            'filters' => $filters,
            'totalVisits' => $visits->count(),
        ]);
    }
}
