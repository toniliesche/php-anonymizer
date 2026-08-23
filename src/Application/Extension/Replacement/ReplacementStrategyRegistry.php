<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Replacement;

use InvalidArgumentException;
use Webmozart\Assert\Assert;
use function Safe\preg_match;

// @internal
final readonly class ReplacementStrategyRegistry
{
    /** @var array<string, ReplacementStrategyInterface> */
    private array $strategies;

    /**
     * @param iterable<mixed> $strategies
     */
    public function __construct(iterable $strategies)
    {
        $registered = [];
        foreach ($strategies as $strategy) {
            Assert::isInstanceOf($strategy, ReplacementStrategyInterface::class, sprintf('Replacement strategy registry entries must implement %s.', ReplacementStrategyInterface::class));
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

    /**
     * @return list<ReplacementStrategyInterface>
     */
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
