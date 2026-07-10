<?php

namespace App\Models\Rentals;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalInspectionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'rental_inspection_id', 'file_path', 'file_name', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(RentalInspection::class, 'rental_inspection_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
