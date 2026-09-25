<?php

namespace App\Services\Inventory;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Miniatura de la foto del producto.
 *
 * Las fotos se suben tal cual las manda la cámara o el navegador (hasta 5 MB),
 * pero los listados (móvil, Inventario, POS, catálogo público) las muestran en
 * recuadros de 30–140 px: servir el original ahí gasta datos y tarda. Aquí se
 * genera, junto al original, una copia de 320 px en JPEG (~20 KB) que es la que
 * usan esos listados.
 *
 * Nunca interrumpe la subida: si GD no puede con el archivo devuelve null y el
 * listado cae al original (`ProductPhoto::thumb_url`).
 */
class PhotoThumbnailService
{
    /** Lado mayor de la miniatura, en píxeles. */
    public const MAX_SIDE = 320;

    /** Calidad JPEG (suficiente para miniaturas, peso bajo). */
    private const QUALITY = 75;

    /**
     * Genera la miniatura del archivo subido y devuelve su ruta relativa en el
     * disco `public`, o null si no se pudo.
     */
    public function store(UploadedFile $file, string $dir): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;   // sin GD: el listado usa el original
        }

        try {
            $raw = @file_get_contents($file->getRealPath());
            if ($raw === false || $raw === '') {
                return null;
            }

            $image = @imagecreatefromstring($raw);
            if ($image === false) {
                return null;
            }

            $image = $this->autoRotate($image, $file->getRealPath());
            $thumb = $this->scale($image);
            imagedestroy($image);

            if ($thumb === null) {
                return null;
            }

            ob_start();
            imagejpeg($thumb, null, self::QUALITY);
            $bytes = (string) ob_get_clean();
            imagedestroy($thumb);

            if ($bytes === '') {
                return null;
            }

            $path = rtrim($dir, '/') . '/' . Str::random(40) . '_thumb.jpg';
            Storage::disk('public')->put($path, $bytes);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar la miniatura de la foto', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** Borra la miniatura (si la hay). Se llama al reemplazar o quitar la foto. */
    public function delete(?string $thumbPath): void
    {
        if ($thumbPath) {
            Storage::disk('public')->delete($thumbPath);
        }
    }

    /**
     * Reduce al lado mayor permitido sobre lienzo blanco (así los PNG con
     * transparencia no salen con fondo negro al pasar a JPEG).
     *
     * @param  \GdImage  $image
     * @return \GdImage|null
     */
    private function scale($image)
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if ($w < 1 || $h < 1) {
            return null;
        }

        $ratio = min(self::MAX_SIDE / max($w, $h), 1);   // nunca agranda
        $nw    = max(1, (int) round($w * $ratio));
        $nh    = max(1, (int) round($h * $ratio));

        $canvas = imagecreatetruecolor($nw, $nh);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $canvas;
    }

    /**
     * Endereza la foto según el EXIF del celular (si no, las verticales salen
     * acostadas).
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function autoRotate($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        try {
            $exif = @exif_read_data($path);
        } catch (\Throwable) {
            return $image;
        }

        $angle = match ((int) ($exif['Orientation'] ?? 0)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = @imagerotate($image, $angle, 0);
        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
