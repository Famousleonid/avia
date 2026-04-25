<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatabaseBackupController extends Controller
{
    public function store(Request $request, DatabaseBackupService $service): RedirectResponse
    {
        abort_unless($request->user()?->isSystemAdmin(), 403);

        try {
            $path = $service->createBackup();
            $name = basename($path);

            return redirect()->back()->with('success', 'Backup created: ' . $name);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }
}
