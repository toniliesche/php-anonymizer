<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule;

enum ScalarAction: string
{
    case REDACT = 'redact';
    case PRESERVE = 'preserve';
}
