<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
#[ORM\Index(fields: ['userId'])]
#[ORM\HasLifecycleCallbacks]
class Item
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(name: 'user_id', type: 'string')]
    public string $userId;

    #[ORM\Column(type: 'string')]
    public string $name;

    #[ORM\Column(type: 'text')]
    public string $description;

    #[ORM\Column(type: 'string')]
    public string $status;

    #[ORM\Column(name: 'image_filename', type: 'string', options: ['default' => ''])]
    public string $imageFilename = '';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
