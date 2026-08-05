<?php

namespace App\Models\Loyalty;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltySetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'enabled', 'earn_amount', 'earn_points',
        'rounding', 'min_purchase', 'points_label', 'expiration_months', 'public_token',
    ];

    protected $casts = [
        'enabled'           => 'boolean',
        'earn_amount'       => 'decimal:2',
        'earn_points'       => 'integer',
        'min_purchase'      => 'decimal:2',
        'expiration_months' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Devuelve (o crea con defaults) la configuración de una empresa. */
    public static function forCompany(int $companyId): self
    {
        return static::firstOrCreate(['company_id' => $companyId]);
    }

    /** Asegura un token público para el catálogo y lo devuelve. */
    public function ensurePublicToken(): string
    {
        if (!$this->public_token) {
            $this->update(['public_token' => \Illuminate\Support\Str::random(32)]);
        }
        return $this->public_token;
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class, 'company_id', 'company_id');
    }
}
