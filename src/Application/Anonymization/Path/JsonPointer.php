<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Path;

use Override;
use Stringable;

final readonly class JsonPointer implements Stringable
{
    private function __construct(private string $value) {}

    public static function root(): self
    {
        return new self('');
    }

    public static function fromString(string $path): self
    {
        $pointer = self::root();
        foreach (explode('/', ltrim($path, '/')) as $segment) {
            if ($segment !== '') {
                $pointer = $pointer->append(str_replace(['~1', '~0'], ['/', '~'], $segment));
            }
        }

        return $pointer;
    }

    public function append(string|int $segment): self
    {
        $segment = str_replace(['~', '/'], ['~0', '~1'], (string) $segment);

        return new self($this->value . '/' . $segment);
    }

    public function prefix(self $nested): self
    {
        return new self($this->value . $nested);
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
