<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

enum ProfileRoot: string
{
    case MAP = 'map';
    case LIST = 'list';
}
