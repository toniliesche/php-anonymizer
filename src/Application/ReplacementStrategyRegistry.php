<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use InvalidArgumentException;

use function Safe\preg_match;

final readonly class ReplacementStrategyRegistry
{
    /** @var array<string, ReplacementStrategyInterface> */
    private array $strategies;

    /** @param iterable<ReplacementStrategyInterface> $strategies */
    public function __construct(iterable $strategies)
    {
        $registered = [];
        foreach ($strategies as $strategy) {
            $id = $strategy->id();
            $this->assertId($id, 'strategy');
            if (isset($registered[$id])) {
                throw new InvalidArgumentException(sprintf('Replacement strategy "%s" is registered more than once.', $id));
            }

            $registered[$id] = $strategy;
        }

        $this->strategies = $registered;
    }

    public function get(string $id): ?ReplacementStrategyInterface
    {
        return $this->strategies[$id] ?? null;
    }

    /** @return list<ReplacementStrategyInterface> */
    public function all(): array
    {
        return array_values($this->strategies);
    }

    private function assertId(string $id, string $kind): void
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $id) !== 1) {
            throw new InvalidArgumentException(sprintf('The %s ID "%s" is invalid.', $kind, $id));
        }
    }
}
