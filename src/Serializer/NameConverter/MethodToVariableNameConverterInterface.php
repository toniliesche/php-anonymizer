<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter;

interface MethodToVariableNameConverterInterface
{
    public function isSupportedMethodName(string $methodName): bool;

    public function convertMethodToVariableName(string $methodName): string;
}
