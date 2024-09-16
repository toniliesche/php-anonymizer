<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum NodeParser: string
{
    case SIMPLE = 'simple';
    case COMPLEX = 'complex';
}
