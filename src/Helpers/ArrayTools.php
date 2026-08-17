<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Helpers;

use PhpAnonymizer\Anonymizer\Enum\ArrayType;

class ArrayTools
{
    /**
     * @param array<int|string, mixed> $array
     */
    public static function detectType(array $array): ArrayType
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

    /**
     * @param array<int, mixed> $array
     */
    public static function isStringOnlyArray(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_string($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $array
     */
    public static function isObjectOnlyArray(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_object($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $array
     */
    public static function isArrayOnlyArray(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $array
     */
    public static function isScalarOnlyArray(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_scalar($value)) {
                return false;
            }
        }

        return true;
    }
}
