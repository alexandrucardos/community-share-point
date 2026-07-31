<?php

declare(strict_types=1);

namespace App\Application\User\GetUser;

use App\Application\User\UserQueryRepositoryInterface;
use App\Domain\ValueObject\UuidValueObject;

final class GetUserHandler
{
    public function __construct(
        private readonly UserQueryRepositoryInterface $userRepository,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function query(GetUserQuery $loadUserQuery): ?UserView
    {
        return $this->userRepository->findById(($this->uuidValueObject)($loadUserQuery->userId));

    }
}
