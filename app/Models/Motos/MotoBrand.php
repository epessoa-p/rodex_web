<?php

namespace App\Models\Motos;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotoBrand extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'name', 'country', 'active'];

    protected $casts = ['active' => 'boolean', 'deleted_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(MotoModel::class);
    }
}
