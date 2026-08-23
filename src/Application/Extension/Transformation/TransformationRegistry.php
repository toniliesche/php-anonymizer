<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

use InvalidArgumentException;
use function Safe\preg_match;

// @internal
final readonly class TransformationRegistry
{
    /** @var array<string,ValueTransformationInterface> */
    private array $transformations;

    /**
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public function __construct(iterable $transformations)
    {
        $registered = [];
        foreach ($transformations as $transformation) {
            $id = $transformation->id();
            if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $id) !== 1) {
                throw new InvalidArgumentException(sprintf('The transformation ID "%s" is invalid.', $id));
            }
            if (isset($registered[$id])) {
                throw new InvalidArgumentException(sprintf('Value transformation "%s" is registered more than once.', $id));
            }
            $registered[$id] = $transformation;
        }
        $this->transformations = $registered;
    }

    public function get(string $id): ?ValueTransformationInterface
    {
        return $this->transformations[$id] ?? null;
    }

    /**
     * @return list<ValueTransformationInterface>
     */
    public function all(): array
    {
        return array_values($this->transformations);
    }
}
