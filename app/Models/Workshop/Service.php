<?php

namespace App\Models\Workshop;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'price', 'estimated_time', 'active',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
