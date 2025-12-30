<?php

namespace PhpAnonymizer\Anonymizer\Helpers;

use PhpAnonymizer\Anonymizer\Enum\ArrayType;

class ArrayTools
{
    public static function detect(array $array): ArrayType
    {
        if (array_is_list($array)) {
            return ArrayType::LIST;
        }

        foreach (array_keys($array) as $key) {
            if (is_string($key)) {
                continue;
            }

            return ArrayType::MIXED;
        }

        return ArrayType::MAP;
    }
}
