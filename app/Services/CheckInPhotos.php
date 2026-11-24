<?php

namespace App\Services;

use App\Models\CheckIn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores "Tonight" photos safely:
 *  - re-encodes every image with GD, which drops ALL metadata (EXIF, incl. GPS location)
 *  - applies the phone's EXIF rotation first so the photo stays upright
 *  - downsizes to at most 1440px on the long edge (JPEG, quality 82)
 *  - saves to the PRIVATE disk; files are only served through an authorised route
 */
class CheckInPhotos
{
    public const MAX_EDGE = 1440;

    public function store(UploadedFile $file, string $userId): string
    {
        $path = $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (!$image) {
            throw new RuntimeException('That file doesn’t look like a photo.');
        }

        $image = $this->orient($image, $path);
        $image = $this->downsize($image);

        ob_start();
        imagejpeg($image, null, 82);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        $stored = 'check-ins/' . $userId . '/' . Str::uuid() . '.jpg';
        Storage::disk(CheckIn::PHOTO_DISK)->put($stored, $jpeg);

        return $stored;
    }

    private function orient(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function downsize(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, self::MAX_EDGE / max($w, $h));
        if ($scale >= 1) {
            return $image;
        }

        $resized = imagescale($image, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC);
        imagedestroy($image);

        return $resized;
    }
}
