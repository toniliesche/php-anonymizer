<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

/**
 * Immutable input for object adapters.
 *
 * Adapter options are intentionally opaque to the core package. For example,
 * the Symfony Serializer adapter uses them as serializer context options.
 */
/**
 * @codeCoverageIgnore Immutable request accessors are covered by façade integration tests.
 */
final readonly class ObjectAnonymizationRequest
{
    private AnonymizationContext $context;

    /**
     * @param array<string, mixed> $adapterOptions
     */
    public function __construct(
        private string $profile,
        private object $object,
        ?AnonymizationContext $context = null,
        private array $adapterOptions = [],
    ) {
        $this->context = $context ?? new AnonymizationContext();
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function object(): object
    {
        return $this->object;
    }

    public function context(): AnonymizationContext
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function adapterOptions(): array
    {
        return $this->adapterOptions;
    }
}
