<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id', 'method', 'account_details', 'amount', 'status',
        'admin_note', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'processed_at' => 'datetime',
        ];
    }

    public const METHODS = [
        'paypal' => 'PayPal',
        'payoneer' => 'Payoneer',
        'usdt' => 'Crypto (USDT)',
        'local_wallet' => 'Local Wallet',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
