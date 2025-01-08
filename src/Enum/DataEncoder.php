<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum DataEncoder: string
{
    case ARRAY_TO_JSON = 'array2json';
    case CLONE = 'clone';
    case JSON = 'json';
    case NOOP = 'noop';
    case SYMFONY = 'symfony';
    case SYMFONY_TO_ARRAY = 'symfony2array';
    case SYMFONY_TO_JSON = 'symfony2json';
    case YAML = 'yaml';
}
