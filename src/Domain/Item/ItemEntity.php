<?php

namespace App\Domain\Item;

use App\Domain\ValueObject\UuidValueObject;

final class ItemEntity
{
    private string $name;
    private string $description;
    private string $status;
    private string $imageUrl;
    private string $imageFilename = '';
    private UuidValueObject $userId;

    public function __construct(
        private string $id,
    ){
    }
    public function getId(): string
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

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getImageFilename(): string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(string $imageFilename): void
    {
        $this->imageFilename = $imageFilename;
    }

}
