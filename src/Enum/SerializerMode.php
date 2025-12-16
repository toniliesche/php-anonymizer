<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum SerializerMode: string
{
    case AUTOWIRE = 'autowire';
    case CUSTOM = 'custom';
    case INTERNAL = 'internal';
}
