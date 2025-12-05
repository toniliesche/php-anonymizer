<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies;

use Jawira\CaseConverter\Convert;

final class CamelCaseStrategy implements NameConverterStrategyInterface
{
    public function getSeparator(): string
    {
        return '';
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
        return $convert->fromCamel();
    }

    public function join(Convert $convert): string
    {
        return $convert->toCamel();
    }

    public function getFirstGroupMatch(): string
    {
        return '[a-z]+[0-9]*';
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
        return true;
    }
}
