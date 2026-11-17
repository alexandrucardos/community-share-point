<?php

declare(strict_types=1);

namespace App\Service\Image;

use App\Domain\Item\FileExtension;
use App\Domain\Item\ItemEntity;
use AsyncAws\S3\S3Client;

final class ImageUploader
{
    public function __construct(
        private readonly S3Client $s3,
        private readonly string $awsS3Bucket,
        private readonly string $awsS3KeyPrefix,
    ) {
    }

    public function upload(
        ItemEntity $item
    ): void
    {
        $this->encode($item);
    }

    public function delete(ItemEntity $item): void
    {
        $fileName = $item->getFileName();

        if ($fileName === '') {
            return;
        }

        $this->s3->deleteObject([
            'Bucket' => $this->awsS3Bucket,
            'Key' => $this->key($fileName),
        ])->resolve();
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
            $orientedImage = $this->applyOrientation($image, $contents, $item->getFileExtension());
            if ($orientedImage !== $image) {
                imagedestroy($image);
                $image = $orientedImage;
            }

            $thumbnail = $this->resize($image, $item->getFileSize());

            // Preserve transparency for formats that support it.
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);

            try {
                $body = $this->render($thumbnail, $item->getFileExtension());
            } finally {
                if ($thumbnail !== $image) {
                    imagedestroy($thumbnail);
                }
            }
        } finally {
            imagedestroy($image);
        }

        $this->s3->putObject([
            'Bucket' => $this->awsS3Bucket,
            'Key' => $this->key($item->getFileName()),
            'Body' => $body,
            'ContentType' => $this->contentType($item->getFileExtension()),
        ])->resolve();
    }

    private function applyOrientation(\GdImage $image, string $contents, FileExtension $extension): \GdImage
    {
        if (!in_array($extension, [FileExtension::jpg, FileExtension::jpeg], true)
            || !function_exists('exif_read_data')
        ) {
            return $image;
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'image-exif-');

        if (false === $temporaryFile) {
            throw new \RuntimeException('Unable to read the uploaded image orientation.');
        }

        try {
            if (false === file_put_contents($temporaryFile, $contents)) {
                throw new \RuntimeException('Unable to read the uploaded image orientation.');
            }

            $metadata = @exif_read_data($temporaryFile);
            $orientation = is_array($metadata) ? (int) ($metadata['Orientation'] ?? 1) : 1;
        } finally {
            unlink($temporaryFile);
        }

        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->rotateAndFlip($image, 90, IMG_FLIP_VERTICAL),
            6 => $this->rotate($image, -90),
            7 => $this->rotateAndFlip($image, 90, IMG_FLIP_HORIZONTAL),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function rotate(\GdImage $image, int $angle): \GdImage
    {
        $rotated = imagerotate($image, $angle, 0);

        if (false === $rotated) {
            throw new \RuntimeException('Unable to rotate the uploaded image.');
        }

        return $rotated;
    }

    private function rotateAndFlip(\GdImage $image, int $angle, int $mode): \GdImage
    {
        return $this->flip($this->rotate($image, $angle), $mode);
    }

    private function flip(\GdImage $image, int $mode): \GdImage
    {
        if (!imageflip($image, $mode)) {
            throw new \RuntimeException('Unable to flip the uploaded image.');
        }

        return $image;
    }

    /**
     * Encode the GD image into the target format and return the raw bytes.
     */
    private function render(\GdImage $thumbnail, FileExtension $extension): string
    {
        ob_start();

        $encoded = match ($extension) {
            FileExtension::jpg, FileExtension::jpeg => imagejpeg($thumbnail, null, 85),
            FileExtension::png => imagepng($thumbnail),
            FileExtension::gif => imagegif($thumbnail),
            FileExtension::webp => imagewebp($thumbnail, null, 85),
        };

        $body = ob_get_clean();

        if (false === $encoded || false === $body || '' === $body) {
            throw new \RuntimeException('Unable to encode the uploaded image.');
        }

        return $body;
    }

    private function key(string $fileName): string
    {
        return trim($this->awsS3KeyPrefix, '/').'/'.$fileName;
    }

    private function contentType(FileExtension $extension): string
    {
        return match ($extension) {
            FileExtension::jpg, FileExtension::jpeg => 'image/jpeg',
            FileExtension::png => 'image/png',
            FileExtension::gif => 'image/gif',
            FileExtension::webp => 'image/webp',
        };
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
