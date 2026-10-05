<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Currency;
use App\Models\Setting;
use App\Support\AppSettings;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.general', [
            'row' => Settings::row(),
            'currencies' => Currency::orderBy('currencyname')->get(),
            'mail' => [
                'host' => AppSettings::get('mail.host'),
                'port' => AppSettings::get('mail.port', 587),
                'username' => AppSettings::get('mail.username'),
                'has_password' => (bool) AppSettings::get('mail.password'),
                'encryption' => AppSettings::get('mail.encryption', 'tls'),
                'from_address' => AppSettings::get('mail.from_address'),
                'from_name' => AppSettings::get('mail.from_name'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'integer', 'exists:currency,currencyid'],
            'servicecharge' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'checkintime' => ['nullable', 'date_format:H:i'],
            'checkouttime' => ['nullable', 'date_format:H:i'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $row = Settings::row();
        $values = collect($data)->except('logo')->all();
        $values['servicecharge'] = $values['servicecharge'] ?? 0;
        $values['storename'] = $values['title'];

        if ($request->hasFile('logo')) {
            if ($row->logo && str_starts_with($row->logo, 'storage/uploads/')) {
                Storage::disk('public')->delete(substr($row->logo, strlen('storage/')));
            }
            $values['logo'] = 'storage/'.$request->file('logo')->store('uploads/settings', 'public');
        }

        $row->exists ? $row->update($values) : Setting::create($values + ['id' => Settings::ROW_ID, 'splash_logo' => '', 'timezone' => config('app.timezone'), 'dateformat' => 'd M Y']);
        Settings::flush();

        return back()->with('status', 'Hotel settings saved.');
    }

    public function updateMail(Request $request)
    {
        $data = $request->validate([
            'host' => ['nullable', 'string', 'max:190'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:190'],
            'password' => ['nullable', 'string', 'max:190'],
            'encryption' => ['nullable', 'in:tls,ssl,none'],
            'from_address' => ['nullable', 'email', 'max:190'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ]);

        foreach (['host', 'port', 'username', 'encryption', 'from_address', 'from_name'] as $key) {
            AppSettings::set('mail.'.$key, isset($data[$key]) ? (string) $data[$key] : null);
        }
        if (! empty($data['password'])) {
            AppSettings::set('mail.password', $data['password']); // blank keeps the saved password
        }

        return back()->with('status', 'Mail settings saved.');
    }

    public function testMail(Request $request)
    {
        $to = $request->validate(['to' => ['required', 'email']])['to'];
        AppSettings::applyMailConfig();

        try {
            Mail::to($to)->send(new TestMail);
        } catch (\Throwable $e) {
            return back()->withErrors(['mail' => 'The test e-mail could not be sent: '.$e->getMessage()]);
        }

        return back()->with('status', 'A test e-mail was sent to '.$to.'.');
    }
}
