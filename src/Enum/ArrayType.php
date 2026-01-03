<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum ArrayType: string
{
    case LIST = 'list';
    case MAP = 'map';
    case MIXED = 'mixed';
}
