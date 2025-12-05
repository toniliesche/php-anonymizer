<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies;

use Jawira\CaseConverter\Convert;

interface NameConverterStrategyInterface
{
    public function isSupportedInMethodNames(): bool;

    public function getSeparator(): string;

    public function getIsserPrefix(): string;

    public function getGetterPrefix(): string;

    public function getSetterPrefix(): string;

    public function split(Convert $convert): Convert;

    public function join(Convert $convert): string;

    public function getFirstGroupMatch(): string;

    public function getGroupMatch(): string;

    public function isMixedCase(): bool;
}
