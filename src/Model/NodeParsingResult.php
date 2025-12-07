<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;

final readonly class NodeParsingResult
{
    public function __construct(
        public bool $isValid,
        public bool $isArray = false,
        public string $property = '',
        public ?string $dataAccess = null,
        public ?string $valueType = null,
        public ?string $nestedType = null,
        public ?string $nestedRule = null,
        public ?string $filterField = null,
        public ?string $filterValue = null,
    ) {
        if (!is_null($this->nestedType)) {
            if (is_null($this->nestedRule)) {
                throw new RuleDefinitionException('Nested rule must not be null if nested type is not null');
            }

            if (!is_null($this->valueType)) {
                throw new RuleDefinitionException('Value type must be null if nested type is not null');
            }
        }

        if (is_null($this->filterField) xor is_null($this->filterValue)) {
            throw new RuleDefinitionException('Filter value must not be null if filter field is not null or vice-versa');
        }
    }
}
