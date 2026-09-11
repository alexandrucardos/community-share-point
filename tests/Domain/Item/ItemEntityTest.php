<?php

declare(strict_types=1);

namespace App\Tests\Domain\Item;

use App\Domain\Item\Exception\InvalidFileExtensionException;
use App\Domain\Item\FileExtension;
use App\Domain\Item\ItemEntity;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class ItemEntityTest extends TestCase
{
    private ValidatorInterface $validator;

    private UuidValueObject $id;

    private ItemEntity $entity;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->id = new UuidValueObject($this->validator);
        ($this->id)('6f9c2f2e-1c3b-4d5e-8f7a-9b8c7d6e5f4a');
        $this->entity = new ItemEntity($this->id);
    }

    public function testGetIdReturnsIdPassedToConstructor(): void
    {
        self::assertSame($this->id, $this->entity->getId());
    }

    public function testFileSizeIsInitializedToMaxSizeInConstructor(): void
    {
        self::assertSame(300, $this->entity->getFileSize());
    }

    public function testContainsFileDefaultsToFalse(): void
    {
        self::assertFalse($this->entity->isContainsFile());
    }

    public function testUserIdRoundTripsThroughSetterAndGetter(): void
    {
        $userId = new UuidValueObject($this->validator);
        $userId('1f2e3d4c-5b6a-7f8e-9d0c-1b2a3f4e5d6c');

        $this->entity->setUserId($userId);

        self::assertSame($userId, $this->entity->getUserId());
    }

    public function testNameRoundTripsThroughSetterAndGetter(): void
    {
        $this->entity->setName('Drill');

        self::assertSame('Drill', $this->entity->getName());
    }

    public function testDescriptionRoundTripsThroughSetterAndGetter(): void
    {
        $this->entity->setDescription('Cordless drill with two batteries');

        self::assertSame('Cordless drill with two batteries', $this->entity->getDescription());
    }

    public function testStatusRoundTripsThroughSetterAndGetter(): void
    {
        $this->entity->setStatus('Available');

        self::assertSame('Available', $this->entity->getStatus());
    }

    public function testFileNameRoundTripsThroughSetterAndGetter(): void
    {
        $this->entity->setFileName('drill.jpg');

        self::assertSame('drill.jpg', $this->entity->getFileName());
    }

    public function testSetFileContentAndFileContentRoundTrip(): void
    {
        $this->entity->setFileContent('binary-image-data');

        self::assertSame('binary-image-data', $this->entity->getFileContent());
    }

    public function testContainsFileRoundTripsThroughSetterAndGetter(): void
    {
        $this->entity->setContainsFile(true);

        self::assertTrue($this->entity->isContainsFile());
    }

    public function testSetFileExtensionAcceptsAllowedExtensionAndExposesEnumCase(): void
    {
        $this->entity->setFileExtension('png');

        self::assertSame(FileExtension::png, $this->entity->getFileExtension());
    }

    public function testSetFileExtensionAcceptsEveryExtensionFromAll(): void
    {
        foreach (FileExtension::all() as $extension) {
            $entity = new ItemEntity($this->id);
            $entity->setFileExtension($extension);

            self::assertSame(FileExtension::from($extension), $entity->getFileExtension());
        }
    }

    public function testSetFileExtensionThrowsForDisallowedExtension(): void
    {
        $this->expectException(InvalidFileExtensionException::class);
        $this->expectExceptionMessage('Please upload a JPG, JPEG, PNG, GIF, or WEBP image.');

        $this->entity->setFileExtension('exe');
    }
}
