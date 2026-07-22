<?php

namespace App\Domain\User;

use App\Domain\ValueObject\EmailValueObject;

final class UserEntity
{
    private string $contactInfo;
    private string $groupId;
    private EmailValueObject $email;
    private string $password;
    private string $avatarFilename = '';

    public function __construct(
        //make this uuid
        private string $id,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContactInfo(): string
    {
        return $this->contactInfo;
    }

    public function setContactInfo(string $contactInfo): void
    {
        $this->contactInfo = $contactInfo;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): void
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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAvatarFilename(): string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(string $avatarFilename): void
    {
        $this->avatarFilename = $avatarFilename;
    }
}
