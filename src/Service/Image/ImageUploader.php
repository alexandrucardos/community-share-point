<?php

declare(strict_types=1);

namespace App\Service\Image;

use App\Domain\UuidInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ImageUploader
{
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private string $projectDir,
        private UuidInterface $uuid,
    ) {
    }

    public function upload(UploadedFile $file, string $assetSubdirectory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Please upload a JPG, PNG, GIF, or WEBP image.');
        }

        $filename = $this->uuid->generate().'.'.$extension;

        $file->move($this->projectDir.'/assets/'.$assetSubdirectory, $filename);

        return $filename;
    }

    public function delete(string $filename, string $assetSubdirectory): void
    {
        $path = $this->projectDir.'/assets/'.$assetSubdirectory.'/'.$filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}
