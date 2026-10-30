<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GodImage {
    public static function save(UploadedFile $file): string {
        if (!extension_loaded('gd')) {
            throw ValidationException::withMessages([
                'image' => 'Для обработки изображений включите extension=gd в php.ini.'
            ]);
        }

        $info = @getimagesize($file->getRealPath());
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages(['image' => 'Не удалось прочитать изображение.']);
        }

        [$width, $height] = $info;
        if ($width * $height > 12_000_000) {
            throw ValidationException::withMessages(['image' => 'Слишком большое разрешение изображения.']);
        }

        if ($info[2] === IMAGETYPE_WEBP && !function_exists('imagecreatefromwebp')) {
            throw ValidationException::withMessages(['image' => 'В этом PHP не поддерживается WebP.']);
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
        };
        if (!$source) {
            throw ValidationException::withMessages(['image' => 'Ошибка обработки изображения.']);
        }

        // Центрированная обрезка под формат карточки 3:2.
        $targetWidth = 1200;
        $targetHeight = 800;
        $sourceRatio = $width / $height;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $targetRatio);
            $cropX = (int) floor(($width - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
            $cropX = 0;
            $cropY = (int) floor(($height - $cropHeight) / 2);
        }

        $image = imagecreatetruecolor($targetWidth, $targetHeight);
        $background = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $targetWidth, $targetHeight, $background);
        imagecopyresampled($image, $source, 0, 0, $cropX, $cropY,
            $targetWidth, $targetHeight, $cropWidth, $cropHeight);
        imagedestroy($source);

        // Преобразуем в JPEG: одинаковый формат и компактный размер.
        ob_start();
        $saved = imagejpeg($image, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($image);

        if (!$saved || !$bytes) {
            throw ValidationException::withMessages(['image' => 'Не удалось сохранить изображение.']);
        }

        $path = 'gods/' . Str::uuid() . '.jpg';
        if (!Storage::disk('public')->put($path, $bytes)) {
            throw ValidationException::withMessages(['image' => 'Ошибка записи файла.']);
        }
        return $path;
    }

    public static function removeUploaded(?string $path): void {
        // Исходные изображения пяти богов не трогаем.
        $seedImages = ['Zeus.jpg', 'Poseidon.jpg', 'Hades.jpg', 'Athena.jpg', 'Apollonian.jpg'];
        if ($path && preg_match('~^gods/[a-z0-9_-]+\.(?:jpe?g|png|webp)$~i', $path)
            && !in_array(basename($path), $seedImages, true)) {
            Storage::disk('public')->delete($path);
        }
    }
}
