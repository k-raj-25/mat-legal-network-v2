<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['values' => Setting::allValues()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_phone' => ['required', 'string', 'max:30'],
            'company_whatsapp' => ['required', 'regex:/^\d{8,15}$/'],
            'company_email' => ['required', 'email', 'max:190'],
            'company_address' => ['required', 'string', 'max:500'],
            'support_hours' => ['nullable', 'string', 'max:120'],
            'legal_entity_name' => ['required', 'string', 'max:190'],
            'jurisdiction_city' => ['required', 'string', 'max:100'],
            'grievance_officer_name' => ['required', 'string', 'max:120'],
            'grievance_officer_email' => ['required', 'email', 'max:190'],
        ], ['company_whatsapp.regex' => 'Digits only with country code, e.g. 919876543210.']);

        Setting::put($data);
        AuditLog::record('settings.updated', null, ['keys' => array_keys($data)]);

        return back()->with('status', 'Settings saved.');
    }
}
