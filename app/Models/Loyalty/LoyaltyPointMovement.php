<?php

namespace App\Models\Loyalty;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPointMovement extends Model
{
    use BelongsToCompany;

    const TYPES = [
        'earn'   => ['label' => 'Acumulación', 'color' => 'success'],
        'redeem' => ['label' => 'Canje',        'color' => 'danger'],
        'adjust' => ['label' => 'Ajuste',       'color' => 'secondary'],
        'expire' => ['label' => 'Vencimiento',  'color' => 'warning'],
    ];

    protected $fillable = [
        'company_id', 'client_id', 'type', 'points', 'points_remaining', 'expires_at',
        'source_type', 'source_id', 'description', 'user_id',
    ];

    protected $casts = [
        'points'           => 'integer',
        'points_remaining' => 'integer',
        'expires_at'       => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPES[$this->type]['color'] ?? 'secondary';
    }
}
