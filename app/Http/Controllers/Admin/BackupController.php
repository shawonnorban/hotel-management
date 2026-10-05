<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use RuntimeException;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index()
    {
        return view('admin.backups', ['backups' => $this->backups->list()]);
    }

    public function store()
    {
        try {
            $name = $this->backups->create();
            $this->backups->prune(30);
        } catch (RuntimeException $e) {
            return back()->withErrors(['backup' => $e->getMessage()]);
        }

        return back()->with('status', 'Backup '.$name.' created.');
    }

    public function download(string $name)
    {
        try {
            return response()->download($this->backups->path($name));
        } catch (RuntimeException) {
            abort(404);
        }
    }

    public function destroy(string $name)
    {
        try {
            $this->backups->delete($name);
        } catch (RuntimeException) {
            abort(404);
        }

        return back()->with('status', 'Backup deleted.');
    }
}
