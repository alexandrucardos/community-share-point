<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
#[ORM\Index(fields: ['userId'])]
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

    /**
     * The resolved display URL, computed and persisted on write (see
     * {@see \App\Repository\Adaptor\ItemRepositoryAdaptor}). Stored as text
     * because the placeholder fallback is an inline base64 SVG data URI.
     */
    #[ORM\Column(name: 'image_url', type: 'text', options: ['default' => ''])]
    public string $imageUrl = '';
}
