<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\Event\AbstractDomainEvent;

final class UserCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $contactInfo,
        public readonly string $groupId,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'user.created';
    }

    public function aggregateType(): string
    {
        return 'user';
    }

    public function aggregateId(): string
    {
        return $this->userId;
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'contact_info' => $this->contactInfo,
            'group_id' => $this->groupId,
        ];
    }
}
