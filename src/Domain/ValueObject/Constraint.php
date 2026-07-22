<?php

namespace App\Domain\ValueObject;

enum Constraint
{
    case NotBlank;
    case NotNull;
    case Email;
    case Length;
}
