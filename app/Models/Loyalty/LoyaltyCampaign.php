<?php

namespace App\Models\Loyalty;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'multiplier', 'starts_at', 'ends_at', 'active', 'created_by',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'starts_at'  => 'date',
        'ends_at'    => 'date',
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsRunningAttribute(): bool
    {
        $today = now()->toDateString();
        return $this->active
            && $this->starts_at->toDateString() <= $today
            && $this->ends_at->toDateString() >= $today;
    }
}
