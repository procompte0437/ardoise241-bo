<?php

namespace App\Support;

/** Recadre une photo en carré 180×180 et la réencode en JPEG. */
class Thumbnail
{
    public static function make(string $path, int $size = 180): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return $raw;
        }

        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1) {
            imagedestroy($src);

            return null;
        }

        $side = min($width, $height);
        $dst = imagecreatetruecolor($size, $size);
        $background = imagecolorallocate($dst, 243, 215, 122);
        imagefilledrectangle($dst, 0, 0, $size, $size, $background);
        imagecopyresampled($dst, $src, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2), $size, $size, $side, $side);

        ob_start();
        $written = imagejpeg($dst, null, 72);
        $binary = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return $written && is_string($binary) && $binary !== '' ? $binary : null;
    }
}
