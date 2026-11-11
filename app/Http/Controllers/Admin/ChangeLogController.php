<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileChangeLog;
use Illuminate\Http\Request;

class ChangeLogController extends Controller
{
    public function index(Request $request)
    {
        $showAll = $request->boolean('all');
        $q = ProfileChangeLog::with(['lawyer:id,full_name,slug,public_code,status', 'user:id,name'])->latest();
        if (! $showAll) {
            $q->unreviewed();
        }

        return view('admin.changes.index', ['logs' => $q->paginate(30)->withQueryString(), 'showAll' => $showAll]);
    }

    public function review(ProfileChangeLog $log)
    {
        $log->forceFill(['reviewed_at' => now(), 'reviewed_by' => auth()->id()])->save();

        return back()->with('status', 'Marked as reviewed.');
    }

    public function reviewAll()
    {
        $n = ProfileChangeLog::unreviewed()->update(['reviewed_at' => now(), 'reviewed_by' => auth()->id()]);

        return back()->with('status', $n.' change(s) marked as reviewed.');
    }
}
