<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password', 'referral_code', 'referred_by',
    'role', 'status', 'email_verified_at',
])]
#[Hidden(['password', 'remember_token', 'verification_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:4',
        ];
    }

    /* ----------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------- */

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function adViews(): HasMany
    {
        return $this->hasMany(AdView::class);
    }

    /* ----------------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public static function findByReferralCode(?string $code): ?User
    {
        if (! $code) {
            return null;
        }

        return static::where('referral_code', Str::upper(trim($code)))->first();
    }

    /**
     * Credit the wallet and write a ledger row, atomically.
     */
    public function credit(string $amount, string $kind, ?string $description = null, ?string $reference = null): Transaction
    {
        return DB::transaction(function () use ($amount, $kind, $description, $reference) {
            $user = static::whereKey($this->id)->lockForUpdate()->first();
            $user->balance = bcadd((string) $user->balance, $amount, 4);
            $user->save();
            $this->balance = $user->balance;

            return $user->transactions()->create([
                'type' => 'credit',
                'kind' => $kind,
                'amount' => $amount,
                'balance_after' => $user->balance,
                'description' => $description,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Debit the wallet and write a ledger row, atomically.
     * Throws RuntimeException when the balance is insufficient.
     */
    public function debit(string $amount, string $kind, ?string $description = null, ?string $reference = null): Transaction
    {
        return DB::transaction(function () use ($amount, $kind, $description, $reference) {
            $user = static::whereKey($this->id)->lockForUpdate()->first();
            if (bccomp((string) $user->balance, $amount, 4) < 0) {
                throw new \RuntimeException('Insufficient balance.');
            }
            $user->balance = bcsub((string) $user->balance, $amount, 4);
            $user->save();
            $this->balance = $user->balance;

            return $user->transactions()->create([
                'type' => 'debit',
                'kind' => $kind,
                'amount' => $amount,
                'balance_after' => $user->balance,
                'description' => $description,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Debit the wallet even if the balance would go negative, and
     * write a ledger row, atomically. Used for offerwall chargebacks,
     * which must net out a credit that may already have been
     * withdrawn - the plain debit() refuses those.
     */
    public function forceDebit(string $amount, string $kind, ?string $description = null, ?string $reference = null): Transaction
    {
        return DB::transaction(function () use ($amount, $kind, $description, $reference) {
            $user = static::whereKey($this->id)->lockForUpdate()->first();
            $user->balance = bcsub((string) $user->balance, $amount, 4);
            $user->save();
            $this->balance = $user->balance;

            return $user->transactions()->create([
                'type' => 'debit',
                'kind' => $kind,
                'amount' => $amount,
                'balance_after' => $user->balance,
                'description' => $description,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Pay the referrer their commission on this user's earning.
     * Rate comes from the `referral_commission_percent` setting.
     */
    public function payReferralCommission(string $earnedAmount, string $sourceDescription): void
    {
        if (! $this->referred_by) {
            return;
        }

        $percent = (string) Setting::get('referral_commission_percent', '10');
        if (bccomp($percent, '0', 4) <= 0) {
            return;
        }

        $commission = bcdiv(bcmul($earnedAmount, $percent, 8), '100', 4);
        if (bccomp($commission, '0', 4) <= 0) {
            return;
        }

        $referrer = $this->referrer;
        if ($referrer && ! $referrer->isSuspended()) {
            $referrer->credit(
                $commission,
                'referral_commission',
                "Referral commission ({$percent}% of {$sourceDescription} by {$this->name})",
                'ref:'.$this->id
            );
        }
    }
}
