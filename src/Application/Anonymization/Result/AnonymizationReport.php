<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;

final class AnonymizationReport
{
    public function __construct(private readonly bool $collect = true) {}

    public static function collecting(): self
    {
        return new self();
    }

    public static function disabled(): self
    {
        return new self(false);
    }

    public function isCollecting(): bool
    {
        return $this->collect;
    }

    /** @var list<string> */
    private array $anonymizedPaths = [];

    /** @var list<string> */
    private array $missingPaths = [];

    /** @var list<array{path: string, action: string, handler: ?string}> */
    private array $events = [];

    public function anonymized(JsonPointer $path, string $action = 'anonymized', ?string $handler = null): void
    {
        if (!$this->collect) {
            return;
        }
        $this->anonymizedPaths[] = (string) $path;
        $this->events[] = ['path' => (string) $path, 'action' => $action, 'handler' => $handler];
    }

    public function missing(JsonPointer $path): void
    {
        if (!$this->collect) {
            return;
        }
        $this->missingPaths[] = (string) $path;
        $this->events[] = ['path' => (string) $path, 'action' => 'missing', 'handler' => null];
    }

    public function merge(AnonymizationResult $result, JsonPointer $base): void
    {
        foreach ($result->anonymizedPaths() as $path) {
            $this->anonymizedPaths[] = (string) $base->prefix(JsonPointer::fromString($path));
        }
        foreach ($result->missingPaths() as $path) {
            $this->missingPaths[] = (string) $base->prefix(JsonPointer::fromString($path));
        }
        foreach ($result->events() as $event) {
            $this->events[] = [
                'path' => (string) $base->prefix(JsonPointer::fromString($event['path'])),
                'action' => $event['action'],
                'handler' => $event['handler'],
            ];
        }
    }

    public function event(JsonPointer $path, string $action, ?string $handler = null): void
    {
        if (!$this->collect) {
            return;
        }
        $this->events[] = ['path' => (string) $path, 'action' => $action, 'handler' => $handler];
    }

    /** @return list<array{path: string, action: string, handler: ?string}> */
    public function events(): array
    {
        return $this->events;
    }

    /** @return list<string> */
    public function anonymizedPaths(): array
    {
        return $this->anonymizedPaths;
    }

    /** @return list<string> */
    public function missingPaths(): array
    {
        return $this->missingPaths;
    }

}
