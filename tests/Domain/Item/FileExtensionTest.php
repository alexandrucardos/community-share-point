<?php

declare(strict_types=1);

namespace App\Tests\Domain\Item;

use App\Domain\Item\FileExtension;
use PHPUnit\Framework\TestCase;

final class FileExtensionTest extends TestCase
{
    public function testCasesHaveExpectedBackingValues(): void
    {
        self::assertSame('jpg', FileExtension::jpg->value);
        self::assertSame('jpeg', FileExtension::jpeg->value);
        self::assertSame('png', FileExtension::png->value);
        self::assertSame('gif', FileExtension::gif->value);
        self::assertSame('webp', FileExtension::webp->value);
    }

    public function testAllReturnsAllExtensionStrings(): void
    {
        self::assertSame(
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            FileExtension::all()
        );
    }

    public function testAllReturnsOnlyStringExtensionValues(): void
    {
        $all = FileExtension::all();

        self::assertContainsOnly('string', $all);
        foreach ($all as $extension) {
            self::assertNotNull(FileExtension::tryFrom($extension));
        }
    }

    public function testFromInvalidValueThrowsValueError(): void
    {
        $this->expectException(\ValueError::class);

        FileExtension::from('exe');
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        self::assertNull(FileExtension::tryFrom('exe'));
        self::assertSame(FileExtension::png, FileExtension::tryFrom('png'));
    }
}
