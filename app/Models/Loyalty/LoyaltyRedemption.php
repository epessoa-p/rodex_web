<?php

namespace App\Models\Loyalty;

use App\Models\Client;
use App\Models\Company;
use App\Models\Sales\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyRedemption extends Model
{
    protected $fillable = [
        'company_id', 'client_id', 'reward_id', 'points_spent',
        'sale_id', 'status', 'user_id', 'redeemed_at',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'redeemed_at'  => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'reward_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
