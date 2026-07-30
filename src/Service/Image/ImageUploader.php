<?php

declare(strict_types=1);

namespace App\Service\Image;

use App\Domain\UuidInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageUploader
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const THUMBNAIL_MAX_SIZE = 300;

    public function __construct(
        private readonly string $projectDir,
        private readonly UuidInterface $uuid,
    ) {
    }

    public function upload(UploadedFile $file, string $assetSubdirectory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Please upload a JPG, PNG, GIF, or WEBP image.');
        }

        $filename = $this->uuid->generate().'.'.$extension;
        $directory = $this->projectDir.'/assets/'.$assetSubdirectory;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        $this->encode($file, $extension, $directory.'/'.$filename);

        return $filename;
    }

    /**
     * Decodes the uploaded file, scales it down to a thumbnail and re-encodes
     * it through GD, stripping any embedded metadata (EXIF, etc.) and
     * guaranteeing the stored file is a valid image in the requested format.
     */
    private function encode(UploadedFile $file, string $extension, string $target): void
    {
        $contents = file_get_contents($file->getPathname());

        if (false === $contents) {
            throw new \RuntimeException('Unable to read the uploaded image.');
        }

        $image = @imagecreatefromstring($contents);

        if (false === $image) {
            throw new \InvalidArgumentException('The uploaded file is not a valid image.');
        }

        try {
            $thumbnail = $this->resize($image);

            // Preserve transparency for formats that support it.
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);

            try {
                $encoded = match ($extension) {
                    'jpg', 'jpeg' => imagejpeg($thumbnail, $target, 85),
                    'png' => imagepng($thumbnail, $target),
                    'gif' => imagegif($thumbnail, $target),
                    'webp' => imagewebp($thumbnail, $target, 85),
                    default => throw new \InvalidArgumentException('Unsupported image format.'),
                };
            } finally {
                if ($thumbnail !== $image) {
                    imagedestroy($thumbnail);
                }
            }
        } finally {
            imagedestroy($image);
        }

        if (false === $encoded) {
            throw new \RuntimeException('Unable to encode the uploaded image.');
        }
    }

    /**
     * Scales the image down so its longest edge fits THUMBNAIL_MAX_SIZE,
     * preserving the aspect ratio. Images already within bounds are returned
     * unchanged (no upscaling).
     *
     * @param \GdImage $image
     *
     * @return \GdImage
     */
    private function resize(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longestEdge = max($width, $height);

        if ($longestEdge <= self::THUMBNAIL_MAX_SIZE) {
            return $image;
        }

        $scale = self::THUMBNAIL_MAX_SIZE / $longestEdge;
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        if (false === $thumbnail) {
            throw new \RuntimeException('Unable to allocate the thumbnail canvas.');
        }

        // Keep a transparent background rather than the default black.
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
        imagefilledrectangle($thumbnail, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $thumbnail;
    }

    public function delete(string $filename, string $assetSubdirectory): void
    {
        $path = $this->projectDir.'/assets/'.$assetSubdirectory.'/'.$filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}
