<?php

declare(strict_types=1);

namespace App\Tests\Domain\Item;

use App\Domain\Item\ItemStatus;
use PHPUnit\Framework\TestCase;

final class ItemStatusTest extends TestCase
{
    public function testCasesHaveExpectedBackingValues(): void
    {
        self::assertSame('Available', ItemStatus::Available->value);
        self::assertSame('Reserved', ItemStatus::Reserved->value);
        self::assertSame('Deleted', ItemStatus::Deleted->value);
    }

    public function testFromValidValueReturnsMatchingCase(): void
    {
        self::assertSame(ItemStatus::Available, ItemStatus::from('Available'));
        self::assertSame(ItemStatus::Reserved, ItemStatus::from('Reserved'));
        self::assertSame(ItemStatus::Deleted, ItemStatus::from('Deleted'));
    }

    public function testFromInvalidValueThrowsValueError(): void
    {
        $this->expectException(\ValueError::class);

        ItemStatus::from('Borrowed');
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        self::assertNull(ItemStatus::tryFrom('Borrowed'));
        self::assertSame(ItemStatus::Available, ItemStatus::tryFrom('Available'));
    }

    public function testCasesContainsExactlyThreeStatuses(): void
    {
        self::assertCount(3, ItemStatus::cases());
        self::assertContainsOnlyInstancesOf(ItemStatus::class, ItemStatus::cases());
    }
}
