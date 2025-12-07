<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum NodeType: string
{
    case LEAF = 'leaf';
    case NODE = 'node';
}
