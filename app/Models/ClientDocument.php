<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    use HasFactory;

    const TYPES = [
        'ci_front' => 'CI Anverso',
        'ci_back'  => 'CI Reverso',
        'invoice'  => 'Factura',
        'other'    => 'Otro documento',
    ];

    const TYPE_ICONS = [
        'ci_front' => 'bi-card-heading',
        'ci_back'  => 'bi-card-text',
        'invoice'  => 'bi-receipt',
        'other'    => 'bi-file-earmark',
    ];

    protected $fillable = [
        'client_id',
        'company_id',
        'type',
        'label',
        'file_path',
        'file_name',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->type === 'other' && $this->label
            ? $this->label
            : $this->type_label;
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'bi-file-earmark';
    }
}
