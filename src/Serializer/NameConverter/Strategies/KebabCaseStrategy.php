<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies;

use Jawira\CaseConverter\Convert;

final class KebabCaseStrategy implements NameConverterStrategyInterface
{
    public function getSeparator(): string
    {
        return '-';
    }

    public function getGetterPrefix(): string
    {
        return 'get';
    }

    public function getIsserPrefix(): string
    {
        return 'is';
    }

    public function getSetterPrefix(): string
    {
        return 'set';
    }

    public function split(Convert $convert): Convert
    {
        return $convert->fromKebab();
    }

    public function join(Convert $convert): string
    {
        return $convert->toKebab();
    }

    public function getFirstGroupMatch(): string
    {
        return $this->getGroupMatch();
    }

    public function getGroupMatch(): string
    {
        return '[a-z]+[0-9]*';
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
