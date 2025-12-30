<?php

namespace PhpAnonymizer\Anonymizer\Enum;

enum ArrayType: string
{
    case LIST = 'list';
    case MAP = 'map';
    case MIXED = 'mixed';
}
