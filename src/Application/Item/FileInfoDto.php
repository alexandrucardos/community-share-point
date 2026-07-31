<?php

namespace App\Application\Item;

class FileInfoDto
{
    public function __construct(
        public string $fileName,
        public string $fileExtension,
        public string $fileContent
    )
    {
    }

}
