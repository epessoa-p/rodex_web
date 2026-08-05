<?php

namespace App\Models\Rentals;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPenalty extends Model
{
    use BelongsToCompany;

    use HasFactory;

    protected $fillable = [
        'company_id', 'rental_contract_id', 'concept', 'amount', 'penalty_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'penalty_date' => 'date',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function contract(): BelongsTo { return $this->belongsTo(RentalContract::class, 'rental_contract_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
