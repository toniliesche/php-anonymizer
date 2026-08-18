<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

final class RedactUnselectedValues
{
    /** @param list<string> $anonymizedPaths */
    public function apply(mixed &$value, string $path, array &$anonymizedPaths): void
    {
        if (is_string($value)) {
            $value = '[REDACTED]';
            $anonymizedPaths[] = $path;
            return;
        }
        if (!is_array($value)) {
            return;
        }
        foreach ($value as $key => &$item) {
            $this->apply($item, $path . '/' . $this->escape((string) $key), $anonymizedPaths);
        }
        unset($item);
    }

    private function escape(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }
}
