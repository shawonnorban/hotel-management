<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Support\AppSettings;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * WhatsApp: click-to-chat links always work; sending from the system needs the WhatsApp Cloud API
 * (phone-number id + access token from Meta) and is only used when switched on in the settings.
 */
class WhatsAppService
{
    public function apiEnabled(): bool
    {
        return AppSettings::get('whatsapp.enabled') === '1' && AppSettings::get('whatsapp.phone_number_id') && AppSettings::get('whatsapp.token');
    }

    /** International digits only; a leading 0 is replaced by the default country code. */
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $country = preg_replace('/\D+/', '', (string) AppSettings::get('whatsapp.country_code', '')) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && $country !== '') {
            $digits = $country.ltrim($digits, '0');
        }
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw new InvalidArgumentException('Enter a full phone number including the country code.');
        }

        return $digits;
    }

    public function link(string $phone, ?string $text = null): string
    {
        return 'https://wa.me/'.$this->normalize($phone).($text ? '?text='.rawurlencode($text) : '');
    }

    public function send(string $phone, string $text, ?int $userId = null): WhatsappMessage
    {
        if (! $this->apiEnabled()) {
            throw new InvalidArgumentException('WhatsApp sending is not switched on. Fill in the WhatsApp settings first.');
        }
        $to = $this->normalize($phone);

        try {
            $response = Http::withToken((string) AppSettings::get('whatsapp.token'))->timeout(15)
                ->post('https://graph.facebook.com/v20.0/'.AppSettings::get('whatsapp.phone_number_id').'/messages', [
                    'messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text', 'text' => ['body' => $text],
                ]);
            $ok = $response->successful();
            $error = $ok ? null : (string) ($response->json('error.message') ?? 'HTTP '.$response->status());
            $id = $response->json('messages.0.id');
        } catch (\Throwable $e) {
            $ok = false;
            $error = 'Could not reach WhatsApp: '.$e->getMessage();
            $id = null;
        }

        return WhatsappMessage::create(['to' => $to, 'body' => $text, 'status' => $ok ? 'sent' : 'failed', 'provider_id' => $id, 'error' => $error ? mb_substr($error, 0, 500) : null, 'created_by' => $userId]);
    }
}
