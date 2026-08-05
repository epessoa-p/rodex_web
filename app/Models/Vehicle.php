<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'client_id', 'brand', 'model', 'engine_cc',
        'year', 'plate', 'color', 'vin', 'notes', 'active',
    ];

    protected $casts = [
        'year'       => 'integer',
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->brand . ' ' . $this->model . ($this->plate ? " ({$this->plate})" : ''));
    }
}
