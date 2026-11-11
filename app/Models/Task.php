<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'url', 'embed_code', 'duration_seconds', 'reward',
        'is_active', 'sort_order', 'daily_limit_per_user',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reward' => 'decimal:4',
            'duration_seconds' => 'integer',
        ];
    }

    public function adViews(): HasMany
    {
        return $this->hasMany(AdView::class);
    }
}
