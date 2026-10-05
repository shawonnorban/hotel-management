<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['max:0'], // honeypot: real visitors leave it empty
        ]);

        $message = ContactMessage::create(collect($data)->except('website')->all());

        if ($to = Settings::get('email')) {
            rescue(fn () => Mail::to($to)->send(new ContactMessageReceived($message)), report: true);
        }

        return redirect()->route('contact')->with('status', 'Thank you! Your message has been sent and we will get back to you soon.');
    }
}
