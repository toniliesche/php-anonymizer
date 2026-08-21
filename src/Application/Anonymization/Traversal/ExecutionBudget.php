<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;

final class ExecutionBudget
{
    private int $visitedNodes = 0;

    private int $embeddedDepth = 0;

    public function __construct(private readonly AnonymizationLimits $limits)
    {
    }

    public function visit(string $profile, JsonPointer $path, int $depth): void
    {
        ++$this->visitedNodes;
        if ($this->visitedNodes > $this->limits->maxVisitedNodes) {
            throw ExecutionLimitExceeded::forPath($profile, 'maxVisitedNodes', $this->limits->maxVisitedNodes, $path);
        }
        if ($depth > $this->limits->maxDepth) {
            throw ExecutionLimitExceeded::forPath($profile, 'maxDepth', $this->limits->maxDepth, $path);
        }
    }

    public function collection(string $profile, JsonPointer $path, int $entries): void
    {
        if ($entries > $this->limits->maxCollectionEntries) {
            throw ExecutionLimitExceeded::forPath($profile, 'maxCollectionEntries', $this->limits->maxCollectionEntries, $path);
        }
    }

    public function inputBytes(string $profile, string $path, int $bytes): void
    {
        if ($bytes > $this->limits->maxInputBytes) {
            throw ExecutionLimitExceeded::forLocation($profile, 'maxInputBytes', $this->limits->maxInputBytes, $path);
        }
    }

    public function enterEmbedded(string $profile, JsonPointer $path): void
    {
        ++$this->embeddedDepth;
        if ($this->embeddedDepth > $this->limits->maxEmbeddedPayloadDepth) {
            throw ExecutionLimitExceeded::forPath($profile, 'maxEmbeddedPayloadDepth', $this->limits->maxEmbeddedPayloadDepth, $path);
        }
    }

    public function leaveEmbedded(): void
    {
        --$this->embeddedDepth;
    }
}
