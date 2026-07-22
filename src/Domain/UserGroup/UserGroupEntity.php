<?php

namespace App\Domain\UserGroup;

class UserGroupEntity
{
    public function __construct(
        private string $id,
    ){
    }
    public function getId(): string
    {
        return $this->id;
    }
}
