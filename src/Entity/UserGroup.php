<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserGroupRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Doctrine persistence model for the `user_groups` table.
 *
 * Like the other records in this namespace it is intentionally not a domain
 * object: it exists only to let Doctrine map rows and to give {@see User}
 * a real foreign key to reference.
 */
#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
#[ORM\Table(name: 'user_groups')]
class UserGroup
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(type: 'uuid')]
    public Uuid $uuid;
}
