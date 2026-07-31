<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(fields: ['groupId'])]
#[ORM\UniqueConstraint(name: 'uniq_users_email_group', columns: ['email', 'group_id'])]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(type: 'string')]
    public string $email;

    #[ORM\Column(type: 'string')]
    public string $password;

    #[ORM\Column(name: 'contact_info', type: 'string')]
    public string $contactInfo;

    #[ORM\Column(name: 'group_id', type: 'string')]
    public string $groupId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
