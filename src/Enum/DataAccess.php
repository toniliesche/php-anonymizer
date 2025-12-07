<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum DataAccess: string
{
    case ARRAY = 'array';
    case AUTODETECT = 'autodetect';
    case DEFAULT = 'default';
    case PROPERTY = 'property';
    case REFLECTION = 'reflection';
    case SETTER = 'setter';
}
