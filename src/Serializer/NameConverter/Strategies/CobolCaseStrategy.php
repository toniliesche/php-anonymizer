<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies;

use Jawira\CaseConverter\Convert;

final class CobolCaseStrategy implements NameConverterStrategyInterface
{
    public function getSeparator(): string
    {
        return '-';
    }

    public function getGetterPrefix(): string
    {
        return 'GET';
    }

    public function getIsserPrefix(): string
    {
        return 'IS';
    }

    public function getSetterPrefix(): string
    {
        return 'SET';
    }

    public function split(Convert $convert): Convert
    {
        // @codeCoverageIgnoreStart
        return $convert->fromCobol();
        // @codeCoverageIgnoreEnd
    }

    public function join(Convert $convert): string
    {
        return $convert->toCobol();
    }

    public function getFirstGroupMatch(): string
    {
        return $this->getGroupMatch();
    }

    public function getGroupMatch(): string
    {
        return '[A-Z]+[0-9]*';
    }

    public function isSupportedInMethodNames(): bool
    {
        return false;
    }

    public function isMixedCase(): bool
    {
        return false;
    }
}
