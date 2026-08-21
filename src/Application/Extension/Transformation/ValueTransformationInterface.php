<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

interface ValueTransformationInterface
{
    public function id(): string;

    /**
     * @return list<string>
     */
    public function supportedTypes(): array;

    public function outputType(): string;

    /**
     * @param array<string,mixed> $options
     */
    public function validateOptions(array $options): void;

    /**
     * @param array<string,mixed> $options
     */
    public function transform(mixed $value, array $options): mixed;
}
