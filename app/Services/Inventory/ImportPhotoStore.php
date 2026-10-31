<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductPhoto;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fotos tomadas en la tabla de importación.
 *
 * Cada foto se sube apenas se toma (ya reducida en el navegador) a una carpeta
 * temporal privada y la fila solo guarda su token. Al confirmar la importación
 * se mueve al producto. Así el "Guardar" no manda decenas de fotos de golpe,
 * que en el hosting chocaría con el límite de tamaño del POST.
 */
class ImportPhotoStore
{
    /** Carpeta temporal en el disco `local` (no público). */
    private const DIR = 'import-photos';

    /** Horas que se conserva una foto sin usar (borradores incluidos). */
    private const KEEP_HOURS = 72;

    /** Máximo de fotos por producto (igual que el formulario del producto). */
    private const MAX_PER_PRODUCT = 8;

    /** Token: uuid + extensión. Es lo único que viaja en la fila. */
    public const TOKEN_REGEX = '/^[0-9a-f\-]{36}\.(jpg|jpeg|png|webp)$/';

    /** Guarda la foto en la carpeta temporal de la empresa y devuelve su token. */
    public function stash(UploadedFile $file, int $companyId): string
    {
        $this->sweep($companyId);

        $ext = strtolower($file->guessExtension() ?: 'jpg');
        $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';
        $token = Str::uuid() . '.' . $ext;

        $file->storeAs($this->dir($companyId), $token, 'local');

        return $token;
    }

    /**
     * Pasa la foto temporal al producto. Queda como principal si el producto no
     * tenía fotos. Devuelve un aviso (string) si no se pudo, o null si todo bien.
     */
    public function attach(Product $product, string $token): ?string
    {
        if (! preg_match(self::TOKEN_REGEX, $token)) {
            return 'la foto no es válida';
        }

        $tmp = $this->dir($product->company_id) . '/' . $token;
        if (! Storage::disk('local')->exists($tmp)) {
            return 'la foto ya no está disponible, vuelve a tomarla desde el producto';
        }

        $count = $product->photos()->count();
        if ($count >= self::MAX_PER_PRODUCT) {
            Storage::disk('local')->delete($tmp);
            return 'el producto ya tiene ' . self::MAX_PER_PRODUCT . ' fotos';
        }

        $dir  = "company/{$product->company_id}/products/{$product->id}";
        $path = $dir . '/' . Str::random(40) . '.' . pathinfo($token, PATHINFO_EXTENSION);
        Storage::disk('public')->put($path, Storage::disk('local')->get($tmp));

        $local = new UploadedFile(Storage::disk('local')->path($tmp), $token, null, null, true);
        $thumb = app(PhotoThumbnailService::class)->store($local, $dir);

        try {
            ProductPhoto::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'file_path'  => $path,
                'thumb_path' => $thumb,
                'file_name'  => 'importacion-' . $token,
                'is_main'    => ! $product->photos()->where('is_main', true)->exists(),
                'sort_order' => $count,
            ]);
        } catch (\Throwable $e) {
            // Sin registro no deben quedar archivos sueltos en el disco público.
            Storage::disk('public')->delete(array_filter([$path, $thumb]));
            throw $e;
        }

        Storage::disk('local')->delete($tmp);

        return null;
    }

    /** Borra las fotos temporales viejas de la empresa (tomadas y nunca guardadas). */
    private function sweep(int $companyId): void
    {
        $limit = now()->subHours(self::KEEP_HOURS)->getTimestamp();
        $disk  = Storage::disk('local');

        foreach ($disk->files($this->dir($companyId)) as $file) {
            if ($disk->lastModified($file) < $limit) {
                $disk->delete($file);
            }
        }
    }

    private function dir(int $companyId): string
    {
        return self::DIR . '/' . $companyId;
    }
}
