<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;

readonly class NodeParsingResult
{
    public function __construct(
        public bool $isValid,
        public bool $isArray = false,
        public string $property = '',
        public ?string $dataAccess = null,
        public ?string $valueType = null,
        public ?string $nestedType = null,
        public ?string $nestedRule = null,
    ) {
        if (!is_null($this->nestedType)) {
            if (is_null($this->nestedRule)) {
                throw new RuleDefinitionException('Nested rule must not be null if nested type is not null');
            }

            if (!is_null($this->valueType)) {
                throw new RuleDefinitionException('Value type must be null if nested type is not null');
            }
        }
    }
}
