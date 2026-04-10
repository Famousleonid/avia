<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TestSuiteRunnerService;

class TestDashboardController extends Controller
{
    public function __construct(private readonly TestSuiteRunnerService $runner)
    {
    }

    public function index()
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        return view('admin.tests.index', [
            'suites' => $this->runner->allResults(),
        ]);
    }

    public function run(string $suite)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $result = $this->runner->run($suite);

        return redirect()
            ->route('admin.tests.index')
            ->with($result['status'] === 'passed' ? 'success' : 'warning', $result['label'] . ': ' . $result['summary']);
    }
}
