<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WhatsAppController extends Controller
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function settings()
    {
        return view('admin.whatsapp.settings', [
            'enabled' => AppSettings::get('whatsapp.enabled') === '1',
            'phoneNumberId' => AppSettings::get('whatsapp.phone_number_id'),
            'hasToken' => (bool) AppSettings::get('whatsapp.token'),
            'countryCode' => AppSettings::get('whatsapp.country_code'),
            'greeting' => AppSettings::get('whatsapp.greeting', 'Hello {guest}, this is {hotel}. Regarding your booking {booking}: '),
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'enabled' => ['nullable', 'boolean'], 'phone_number_id' => ['nullable', 'digits_between:5,30'], 'token' => ['nullable', 'string', 'max:500'],
            'country_code' => ['nullable', 'digits_between:1,4'], 'greeting' => ['nullable', 'string', 'max:300'],
        ]);
        if ($request->boolean('enabled') && (! ($d['phone_number_id'] ?? AppSettings::get('whatsapp.phone_number_id')) || ! ($d['token'] ?? AppSettings::get('whatsapp.token')))) {
            return back()->withInput()->withErrors(['enabled' => 'Enter the phone-number ID and access token to switch sending on.']);
        }

        AppSettings::set('whatsapp.enabled', $request->boolean('enabled') ? '1' : '0');
        AppSettings::set('whatsapp.phone_number_id', $d['phone_number_id'] ?? null);
        AppSettings::set('whatsapp.country_code', $d['country_code'] ?? null);
        AppSettings::set('whatsapp.greeting', $d['greeting'] ?? null);
        if (! empty($d['token'])) { // blank keeps the stored token
            AppSettings::set('whatsapp.token', $d['token']);
        }

        return back()->with('status', 'WhatsApp settings saved.');
    }

    public function messages()
    {
        return view('admin.whatsapp.messages', ['messages' => WhatsappMessage::orderByDesc('id')->paginate(25), 'apiEnabled' => $this->whatsapp->apiEnabled()]);
    }

    public function send(Request $request)
    {
        $d = $request->validate(['to' => ['required', 'string', 'max:30'], 'body' => ['required', 'string', 'max:1000']]);
        try {
            $message = $this->whatsapp->send($d['to'], $d['body'], auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['to' => $e->getMessage()]);
        }

        return $message->status === 'sent'
            ? back()->with('status', 'Message sent.')
            : back()->withInput()->withErrors(['to' => 'WhatsApp refused the message: '.$message->error]);
    }
}
