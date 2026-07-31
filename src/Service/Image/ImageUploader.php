<?php

declare(strict_types=1);

namespace App\Service\Image;

use App\Domain\Item\FileExtension;
use App\Domain\Item\ItemEntity;

final class ImageUploader
{
    private const IMAGE_SUBDIRECTORY = 'images/items';

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function upload(
        ItemEntity $item
    ): void
    {
        $directory = $this->projectDir.'/assets/'.self::IMAGE_SUBDIRECTORY;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        $this->encode($item);
    }

    private function encode(
        ItemEntity $item,
    ): void
    {
        $contents = $item->getFileContent();

        if (false === $contents) {
            throw new \RuntimeException('Unable to read the uploaded image.');
        }

        $image = @imagecreatefromstring($contents);

        if (false === $image) {
            throw new \InvalidArgumentException('The uploaded file is not a valid image.');
        }

        try {
            $thumbnail = $this->resize($image, $item->getFileSize());

            // Preserve transparency for formats that support it.
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);


            $directory = $this->projectDir.'/assets/'.self::IMAGE_SUBDIRECTORY;
            $target = $directory.'/'.$item->getFileName().'.'.$item->getFileExtension()->value;

            try {
                $encoded = match ($item->getFileExtension()) {
                    FileExtension::jpg, FileExtension::jpeg => imagejpeg($thumbnail, $target, 85),
                    FileExtension::png => imagepng($thumbnail, $target),
                    FileExtension::gif => imagegif($thumbnail, $target),
                    FileExtension::webp => imagewebp($thumbnail, $target, 85),
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

    private function resize(\GdImage $image, int $maxFileSize): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longestEdge = max($width, $height);

        if ($longestEdge <= $maxFileSize) {
            return $image;
        }

        $scale = $maxFileSize / $longestEdge;
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
}
