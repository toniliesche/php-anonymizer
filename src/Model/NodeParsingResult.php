<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

readonly class NodeParsingResult
{
    public function __construct(
        public bool $isValid,
        public bool $isArray = false,
        public string $property = '',
        public ?string $dataAccess = null,
        public ?string $valueType = null,
    ) {
    }
}
