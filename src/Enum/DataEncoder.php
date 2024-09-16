<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum DataEncoder: string
{
    case CLONE = 'clone';
    case JSON = 'json';
    case NOOP = 'noop';
    case SYMFONY = 'symfony';
    case YAML = 'yaml';

}
