<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

enum ProfileMode: string
{
    case REDACT = 'redact';
    case PRESERVE = 'preserve';
}
