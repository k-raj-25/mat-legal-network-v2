<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function __invoke()
    {
        return view('admin.audit', ['logs' => AuditLog::with('user:id,name')->latest()->paginate(50)]);
    }
}
