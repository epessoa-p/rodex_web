<?php

namespace App\Console\Commands;

use App\Models\ClientDocument;
use App\Models\Scopes\CompanyScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mueve los documentos de cliente que quedaron en el disco público
 * (accesibles por URL adivinable) al disco privado, segmentados por empresa.
 *
 * Idempotente: los que ya están en privado se saltan.
 */
class MigrateClientDocumentsToPrivate extends Command
{
    protected $signature = 'clients:documents-to-private {--dry-run : Solo muestra lo que haría}';

    protected $description = 'Mueve los documentos de cliente del disco público al privado (company/{id}/clients/{id}/documents)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Sin scope de empresa: es una tarea de sistema sobre todos los tenants.
        $documents = ClientDocument::withoutGlobalScope(CompanyScope::class)->get();

        $moved = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($documents as $doc) {
            if (Storage::disk('local')->exists($doc->file_path)) {
                $skipped++;
                continue;
            }

            if (! Storage::disk('public')->exists($doc->file_path)) {
                $this->warn("Archivo no encontrado en ningún disco: {$doc->file_path} (documento #{$doc->id})");
                $missing++;
                continue;
            }

            $newPath = sprintf(
                'company/%d/clients/%d/documents/%s',
                $doc->company_id,
                $doc->client_id,
                basename($doc->file_path)
            );

            $this->line(($dryRun ? '[dry-run] ' : '') . "#{$doc->id}: {$doc->file_path} -> {$newPath}");

            if (! $dryRun) {
                Storage::disk('local')->put(
                    $newPath,
                    Storage::disk('public')->get($doc->file_path)
                );

                Storage::disk('public')->delete($doc->file_path);

                $doc->file_path = $newPath;
                $doc->saveQuietly();
            }

            $moved++;
        }

        $this->newLine();
        $this->info("Movidos: {$moved} | Ya privados: {$skipped} | No encontrados: {$missing}");

        if ($dryRun) {
            $this->comment('Dry-run: no se modificó nada. Ejecuta sin --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
