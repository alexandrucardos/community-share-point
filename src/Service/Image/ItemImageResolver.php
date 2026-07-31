<?php

declare(strict_types=1);

namespace App\Service\Image;


final class ItemImageResolver
{
    private const COLOR_PALETTE = ['2563eb', 'db2777', 'ea580c', '65a30d', '7c3aed', '0891b2'];

    public function __construct(
        private readonly string $imagesBasePath,
    ) {
    }

    public function url(string $imageFilename, string $name): string
    {
        return $imageFilename !== ''
            ? $this->imagesBasePath.'/'.$imageFilename
            : $this->placeholderImage($name);
    }

    private function placeholderImage(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, \PREG_SPLIT_NO_EMPTY);
        $initials = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ''));
        $color = self::COLOR_PALETTE[crc32($name) % count(self::COLOR_PALETTE)];

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200">
                <rect width="300" height="200" fill="#{$color}"/>
                <text x="150" y="112" font-family="sans-serif" font-size="64" fill="#ffffff" text-anchor="middle">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
