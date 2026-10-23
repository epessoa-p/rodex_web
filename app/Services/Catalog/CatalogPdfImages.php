<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Storage;

/**
 * Fotos de los productos para el PDF del catálogo, embebidas en base64
 * (dompdf no resuelve bien URLs en todos los hostings).
 *
 * Se parte de la miniatura de 320 px (o del original si es una foto vieja) y
 * se reduce a 150 px en JPEG: ~5 KB por producto, para que un PDF de cientos
 * de productos con foto siga siendo liviano.
 */
class CatalogPdfImages
{
    private const SIDE = 150;
    private const QUALITY = 70;

    /**
     * @param  iterable  $products  productos con la relación `photos` cargada
     * @return array<int, string>  [product_id => data URI]
     */
    public function forProducts(iterable $products): array
    {
        $disk = Storage::disk('public');
        $out = [];

        foreach ($products as $p) {
            $photo = $p->mainPhoto();
            $path = $photo?->thumb_path ?: $photo?->file_path;
            if (! $path || ! $disk->exists($path)) {
                continue;
            }
            $data = $this->shrink($disk->get($path));
            if ($data !== null) {
                $out[$p->id] = $data;
            }
        }

        return $out;
    }

    /** Reduce la imagen a SIDE px (lado mayor) en JPEG; sin GD, la deja como está. */
    private function shrink(string $bytes): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return 'data:image/jpeg;base64,' . base64_encode($bytes);
        }
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        // Fondo blanco (PNG con transparencia).
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, self::QUALITY);
        $jpg = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return $jpg ? 'data:image/jpeg;base64,' . base64_encode($jpg) : null;
    }
}
