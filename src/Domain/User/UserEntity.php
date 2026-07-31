<?php

namespace App\Domain\User;

use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

final class UserEntity
{
    private ContactInfoValueObject $contactInfo;
    private UuidValueObject $groupId;
    private EmailValueObject $email;
    private string $hashedPassword;

    public function __construct(
        private readonly UuidValueObject $id,
    )
    {
    }

    public function getId(): UuidValueObject
    {
        return $this->id;
    }

    public function getContactInfo(): ContactInfoValueObject
    {
        return $this->contactInfo;
    }

    public function setContactInfo(ContactInfoValueObject $contactInfo): void
    {
        $this->contactInfo = $contactInfo;
    }

    public function getGroupId(): UuidValueObject
    {
        return $this->groupId;
    }

    public function setGroupId(UuidValueObject $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getEmail(): EmailValueObject
    {
        return $this->email;
    }

    public function setEmail(EmailValueObject $email): void
    {
        $this->email = $email;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function setHashedPassword(string $hashedPassword): void
    {
        $this->hashedPassword = $hashedPassword;
    }
}
