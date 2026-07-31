<?php

namespace App\Domain\Item;

enum FileExtension: string
{
    case jpg = 'jpg';
    case jpeg = 'jpeg';
    case png = 'png';
    case gif = 'gif';
    case webp = 'webp';

    public static function all(): array
    {
        return [
            FileExtension::jpg->value,
            FileExtension::jpeg->value,
            FileExtension::png->value,
            FileExtension::gif->value,
            FileExtension::webp->value,
        ];
    }
}
