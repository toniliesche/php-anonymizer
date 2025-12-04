<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum RuleSetParser: string
{
    case ARRAY = 'array';
    case DEFAULT = 'default';
    case REGEXP = 'regexp';
}
