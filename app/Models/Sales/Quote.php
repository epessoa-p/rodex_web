<?php

namespace App\Models\Sales;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    const STATUSES = [
        'draft'     => ['label' => 'Borrador',  'color' => 'secondary'],
        'sent'      => ['label' => 'Enviada',   'color' => 'info'],
        'accepted'  => ['label' => 'Aceptada',  'color' => 'success'],
        'rejected'  => ['label' => 'Rechazada', 'color' => 'danger'],
        'expired'   => ['label' => 'Vencida',   'color' => 'warning'],
        'converted' => ['label' => 'Convertida','color' => 'primary'],
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'code', 'status',
        'quote_date', 'valid_until', 'subtotal', 'discount', 'tax', 'total',
        'notes', 'converted_sale_id', 'created_by',
    ];

    protected $casts = [
        'quote_date'  => 'date',
        'valid_until' => 'date',
        'subtotal'    => 'decimal:2',
        'discount'    => 'decimal:2',
        'tax'         => 'decimal:2',
        'total'       => 'decimal:2',
        'deleted_at'  => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    public function getClientNameAttribute(): string
    {
        return $this->client?->full_name ?? 'Cliente general';
    }

    public function isConvertible(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'accepted']);
    }
}
