<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostbackLog extends Model
{
    protected $fillable = [
        'txn_id', 'user_id', 'offer_id', 'payout', 'status',
        'payload', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['payout' => 'decimal:4'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
