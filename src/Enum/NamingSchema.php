<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum NamingSchema: string
{
    // example: Ada_Case_Variable
    case ADA_CASE = 'adaCase';

    // example: camelCaseVariable
    case CAMEL_CASE = 'camelCase';

    // example: COBOL-CASE-VARIABLE
    case COBOL_CASE = 'cobolCase';

    // example: kebab-case-variable
    case KEBAB_CASE = 'kebabCase';

    // example: MACRO_CASE_VARIABLE
    case MACRO_CASE = 'macroCase';

    // example: PascalCaseVariable
    case PASCAL_CASE = 'pascalCase';

    // example: snake_case_variable
    case SNAKE_CASE = 'snakeCase';

    // example: Train-Case-Variable
    case TRAIN_CASE = 'trainCase';
}
