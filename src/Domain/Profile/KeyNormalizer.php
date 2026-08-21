<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use function Safe\preg_replace;

final class KeyNormalizer
{
    public static function normalize(string $key): string
    {
        $key = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $key);
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $key);
        $key = preg_replace('/([A-Za-z])(\d)/', '$1 $2', $key);
        $key = preg_replace('/(\d)([A-Za-z])/', '$1 $2', $key);
        $key = preg_replace('/[^A-Za-z0-9]+/', ' ', $key);

        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $key)), 'UTF-8');
    }
}
