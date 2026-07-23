<?php

namespace App\Domain\ValueObject;

enum Constraint
{
    case NotBlank;
    case NotNull;
    case Email;
    case Length;
    case Uuid;

    const LENGTH_MAX = 'max';
    const LENGTH_MAX_MSG = 'maxMessage';
    const LENGTH_MIN = 'min';
    const LENGTH_MIN_MSG = 'minMessage';
    const NOT_BLANK_MSG = 'message';

}
