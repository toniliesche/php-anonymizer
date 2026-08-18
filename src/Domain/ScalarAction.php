<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain;

enum ScalarAction: string
{
    case REDACT = 'redact';
    case PRESERVE = 'preserve';
}
