<?php

namespace App\Domain\Item;

enum FileExtension: string
{
    case jpg = 'jpg';
    case jpeg = 'jpeg';
    case png = 'png';
    case gif = 'gif';
    case webp = 'webp';
}
