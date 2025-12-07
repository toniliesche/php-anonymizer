<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies;

use Jawira\CaseConverter\Convert;

final class AdaCaseStrategy implements NameConverterStrategyInterface
{
    public function getSeparator(): string
    {
        return '_';
    }

    public function getGetterPrefix(): string
    {
        return 'Get';
    }

    public function getIsserPrefix(): string
    {
        return 'Is';
    }

    public function getSetterPrefix(): string
    {
        return 'Set';
    }

    public function split(Convert $convert): Convert
    {
        return $convert->fromAda();
    }

    public function join(Convert $convert): string
    {
        return $convert->toAda();
    }

    public function getFirstGroupMatch(): string
    {
        return $this->getGroupMatch();
    }

    public function getGroupMatch(): string
    {
        return '[A-Z][a-z]*[0-9]*';
    }

    public function isSupportedInMethodNames(): bool
    {
        return true;
    }

    public function isMixedCase(): bool
    {
        return false;
    }
}
