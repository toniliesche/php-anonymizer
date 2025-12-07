<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum NodeParser: string
{
    case ARRAY = 'array';
    case DEFAULT = 'default';
    case SIMPLE = 'simple';
    case COMPLEX = 'complex';
}
