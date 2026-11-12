<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'kind', 'amount', 'balance_after',
        'description', 'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'balance_after' => 'decimal:4',
        ];
    }

    /** Kinds that count as "earnings" for dashboard statistics. */
    public const EARNING_KINDS = ['ad_view', 'offerwall', 'referral_commission', 'bonus'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'ad_view' => 'Ad view reward',
            'offerwall' => str_starts_with((string) $this->reference, 'bitcotasks:')
                ? 'Offerwall (BitcoTasks)'
                : 'Offerwall (CPAGrip)',
            'referral_commission' => 'Referral commission',
            'withdrawal' => 'Withdrawal',
            'withdrawal_refund' => 'Withdrawal refund',
            'manual_adjust' => 'Manual adjustment',
            'bonus' => 'Bonus',
            default => ucfirst(str_replace('_', ' ', $this->kind)),
        };
    }
}
