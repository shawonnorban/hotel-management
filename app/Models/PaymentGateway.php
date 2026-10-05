<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    /** driver => [label, default payment method id, credential fields: key => label] */
    public const DRIVERS = [
        'stripe' => ['label' => 'Stripe', 'method' => 7, 'fields' => ['secret_key' => 'Secret key', 'webhook_secret' => 'Webhook signing secret']],
        'paypal' => ['label' => 'PayPal', 'method' => 3, 'fields' => ['client_id' => 'Client ID', 'client_secret' => 'Client secret']],
        'sslcommerz' => ['label' => 'SSLCommerz', 'method' => 5, 'fields' => ['store_id' => 'Store ID', 'store_password' => 'Store password']],
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['live' => 'boolean', 'credentials' => 'encrypted:array'];
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function credential(string $key): ?string
    {
        return ($this->credentials ?? [])[$key] ?? null;
    }

    public function isConfigured(): bool
    {
        foreach (array_keys(self::DRIVERS[$this->driver]['fields']) as $key) {
            // Stripe's webhook secret is optional; every other field is required.
            if ($key !== 'webhook_secret' && ! $this->credential($key)) {
                return false;
            }
        }

        return true;
    }

    public function getLabelAttribute(): string
    {
        return self::DRIVERS[$this->driver]['label'] ?? $this->driver;
    }
}
