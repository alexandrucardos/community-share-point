<?php

declare(strict_types=1);

namespace App\Tests\Domain\UserGroup;

use App\Domain\UserGroup\UserGroupEntity;
use PHPUnit\Framework\TestCase;

final class UserGroupEntityTest extends TestCase
{
    public function testGetIdReturnsIdPassedToConstructor(): void
    {
        $entity = new UserGroupEntity('7c1f4a2b-3d5e-4f6a-8b9c-0d1e2f3a4b5c');

        self::assertSame('7c1f4a2b-3d5e-4f6a-8b9c-0d1e2f3a4b5c', $entity->getId());
    }
}
