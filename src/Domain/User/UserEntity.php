<?php

namespace App\Domain\User;

final class UserEntity
{
    private string $contactInfo;
    private string $groupId;
    private string $email;
    private string $password;

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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
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
}
