<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;

final readonly class AnonymizationLimits
{
    public function __construct(
        public int $maxInputBytes = 10_485_760,
        public int $maxDepth = 64,
        public int $maxVisitedNodes = 100_000,
        public int $maxCollectionEntries = 50_000,
        public int $maxEmbeddedPayloadDepth = 8,
    ) {
        foreach (get_object_vars($this) as $name => $value) {
            if ($value < 1) {
                throw new InvalidArgumentException(sprintf('%s must be greater than zero.', $name));
            }
        }
    }
}
