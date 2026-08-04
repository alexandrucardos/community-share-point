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
