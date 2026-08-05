<?php

namespace App\Models\Workshop;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPhoto extends Model
{
    use BelongsToCompany;

    use HasFactory;

    protected $fillable = [
        'company_id', 'work_order_id', 'file_path', 'file_name', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
