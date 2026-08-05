<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClientDocument extends Model
{
    use BelongsToCompany;

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

    /**
     * Disco donde vive el archivo.
     *
     * Los documentos nuevos se guardan en el disco privado ('local') bajo
     * company/{id}/..., fuera de public/. Los antiguos quedaron en el disco
     * 'public' (accesibles por URL); se siguen sirviendo hasta que se migren
     * con `php artisan clients:documents-to-private`.
     */
    public function resolveDisk(): string
    {
        return Storage::disk('local')->exists($this->file_path) ? 'local' : 'public';
    }

    /**
     * URL de acceso al documento: siempre pasa por la ruta autorizada, que
     * comprueba empresa y permisos antes de entregar el archivo.
     */
    public function getFileUrlAttribute(): string
    {
        return route('clients.documents.download', $this);
    }

    public function getIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'bi-file-earmark';
    }
}
