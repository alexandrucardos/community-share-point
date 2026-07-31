<?php

namespace App\Domain\Item;

use App\Domain\Item\Exception\InvalidFileExtensionException;
use App\Domain\ValueObject\FileValueObject;
use App\Domain\ValueObject\UuidValueObject;

final class ItemEntity
{
    private const FILE_MAX_SIZE = 300;

    private string $name;
    private string $description;
    private string $status;
    private UuidValueObject $userId;
    private FileExtension $fileExtension;
    private string $fileName;
    private int $fileSize;

    private string $fileContent;

    private bool $containsFile = false;

    public function __construct(
        private UuidValueObject $id,
    ){
        $this->fileSize = self::FILE_MAX_SIZE;
    }
    public function getId(): UuidValueObject
    {
        return $this->id;
    }

    public function getUserId(): UuidValueObject
    {
        return $this->userId;
    }

    public function setUserId(UuidValueObject $userId): void
    {
        $this->userId = $userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setFileExtension(string $fileExtension): void
    {
        if (!in_array($fileExtension, FileExtension::cases(), true)) {
            throw new InvalidFileExtensionException('Please upload a JPG, PNG, GIF, or WEBP image.');
        }

        $this->fileExtension = FileExtension::from($fileExtension);
    }

    public function getFileExtension(): FileExtension
    {
        return $this->fileExtension;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileContent(string $fileContent): void
    {
        $this->fileContent = $fileContent;
    }
    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function isContainsFile(): bool
    {
        return $this->containsFile;
    }
    public function setContainsFile(bool $containsFile): void
    {
        $this->containsFile = $containsFile;
    }
}
