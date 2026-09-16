<?php

declare(strict_types=1);

namespace App\Tests\Service\RequestLog;

use App\Service\RequestLog\PayloadTruncator;
use PHPUnit\Framework\TestCase;

final class PayloadTruncatorTest extends TestCase
{
    private PayloadTruncator $truncator;

    protected function setUp(): void
    {
        $this->truncator = new PayloadTruncator();
    }

    public function testLeavesSmallPayloadsUntouched(): void
    {
        $payload = ['name' => 'Drill', 'quantity' => 2, 'nested' => ['ok' => true]];

        self::assertSame($payload, $this->truncator->truncate($payload));
    }

    public function testShortensLongStringsAndReportsTheirOriginalLength(): void
    {
        $payload = $this->truncator->truncate(['body' => str_repeat('a', 5000)], 100);

        self::assertSame('body', array_key_first($payload));
        self::assertStringStartsWith(str_repeat('a', 50), $payload['body']);
        self::assertStringContainsString('[5000 chars]', $payload['body']);
        self::assertLessThan(200, strlen($payload['body']));
    }

    public function testShortensNestedValuesWithinTheSameBudget(): void
    {
        $payload = $this->truncator->truncate(['items' => ['first' => str_repeat('a', 5000)]], 50);

        self::assertStringContainsString('[5000 chars]', $payload['items']['first']);
    }

    public function testDropsTheRemainingFieldsOnceTheBudgetIsSpent(): void
    {
        $payload = $this->truncator->truncate(
            ['first' => str_repeat('a', 5000), 'second' => 'kept?', 'third' => 'kept?'],
            50,
        );

        self::assertSame('first', array_key_first($payload));
        self::assertStringContainsString('2 fields dropped', (string) $payload['…']);
        self::assertArrayNotHasKey('second', $payload);
        self::assertArrayNotHasKey('third', $payload);
    }

    public function testHandlesNonStringScalars(): void
    {
        $payload = $this->truncator->truncate(['count' => 3, 'active' => true, 'missing' => null]);

        self::assertSame(['count' => 3, 'active' => true, 'missing' => null], $payload);
    }
}
