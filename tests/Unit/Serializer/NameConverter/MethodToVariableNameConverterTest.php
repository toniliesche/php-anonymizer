<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Serializer\NameConverter;

use Generator;
use PhpAnonymizer\Anonymizer\Enum\NamingSchema;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\MethodToVariableNameConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function sprintf;

final class MethodToVariableNameConverterTest extends TestCase
{
    private const METHODS = [
        'ADA' => [
            'namingSchema' => NamingSchema::ADA_CASE->value,
            'supportsMethods' => true,
            'methods' => [
                'GETTER' => [
                    'method' => 'Get_My_Var',
                    'var' => 'My_Var',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'Get_My_Var99',
                    'var' => 'My_Var99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'Set_My_Var',
                    'var' => 'My_Var',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'Read_My_Var',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'Is_My_Var',
                    'var' => 'My_Var',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'CAMEL' => [
            'namingSchema' => NamingSchema::CAMEL_CASE->value,
            'supportsMethods' => true,
            'methods' => [
                'GETTER' => [
                    'method' => 'getMyVar',
                    'var' => 'myVar',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'getMyVar99',
                    'var' => 'myVar99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'setMyVar',
                    'var' => 'myVar',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'readMyVar',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'isMyVar',
                    'var' => 'myVar',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'COBOL' => [
            'namingSchema' => NamingSchema::COBOL_CASE->value,
            'supportsMethods' => false,
            'methods' => [
                'GETTER' => [
                    'method' => 'GET-MY-VAR',
                    'var' => 'MY-VAR',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'GET-MY-VAR99',
                    'var' => 'MY-VAR99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'SET-MY-VAR',
                    'var' => 'MY-VAR',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'READ-MY-VAR',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'IS-MY-VAR',
                    'var' => 'MY-VAR',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'KEBAB' => [
            'namingSchema' => NamingSchema::KEBAB_CASE->value,
            'supportsMethods' => false,
            'methods' => [
                'GETTER' => [
                    'method' => 'get-my-var',
                    'var' => 'my-var',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'get-my-var99',
                    'var' => 'my-var99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'set-my-var',
                    'var' => 'my-var',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'read-my-var',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'is-my-var',
                    'var' => 'my-var',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'MACRO' => [
            'namingSchema' => NamingSchema::MACRO_CASE->value,
            'supportsMethods' => true,
            'methods' => [
                'GETTER' => [
                    'method' => 'GET_MY_VAR',
                    'var' => 'MY_VAR',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'GET_MY_VAR99',
                    'var' => 'MY_VAR99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'SET_MY_VAR',
                    'var' => 'MY_VAR',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'READ_MY_VAR',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'IS_MY_VAR',
                    'var' => 'MY_VAR',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'PASCAL' => [
            'namingSchema' => NamingSchema::PASCAL_CASE->value,
            'supportsMethods' => true,
            'methods' => [
                'GETTER' => [
                    'method' => 'GetMyVar',
                    'var' => 'MyVar',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'GetMyVar99',
                    'var' => 'MyVar99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'SetMyVar',
                    'var' => 'MyVar',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'ReadMyVar',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'IsMyVar',
                    'var' => 'MyVar',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'SNAKE' => [
            'namingSchema' => NamingSchema::SNAKE_CASE->value,
            'supportsMethods' => true,
            'methods' => [
                'GETTER' => [
                    'method' => 'get_my_var',
                    'var' => 'my_var',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'get_my_var99',
                    'var' => 'my_var99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'set_my_var',
                    'var' => 'my_var',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'read_my_var',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'is_my_var',
                    'var' => 'my_var',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
        'TRAIN' => [
            'namingSchema' => NamingSchema::TRAIN_CASE->value,
            'supportsMethods' => false,
            'methods' => [
                'GETTER' => [
                    'method' => 'Get-My-Var',
                    'var' => 'My-Var',
                    'valid' => true,
                    'bool' => false,
                ],
                'GETTER-WITH-NUMBER' => [
                    'method' => 'Get-My-Var99',
                    'var' => 'My-Var99',
                    'valid' => true,
                    'bool' => false,
                ],
                'SETTER' => [
                    'method' => 'Set-My-Var',
                    'var' => 'My-Var',
                    'valid' => true,
                    'bool' => false,
                ],
                'READER' => [
                    'method' => 'Read-My-Var',
                    'valid' => false,
                ],
                'ISSER' => [
                    'method' => 'Is-My-Var',
                    'var' => 'My-Var',
                    'valid' => true,
                    'bool' => true,
                ],
            ],
        ],
    ];

    #[DataProvider('provideMethodsForSupportTests')]
    public function testSupportedMethods(string $namingSchema, string $methodName, bool $isValid): void
    {
        $converter = new MethodToVariableNameConverter(
            methodNamingSchema: $namingSchema,
            varNamingSchema: $namingSchema,
            isserPrefix: true,
        );

        if (!$isValid) {
            self::assertFalse(
                $converter->isSupportedMethodName(
                    $methodName,
                ),
            );

            return;
        }

        self::assertTrue(
            $converter->isSupportedMethodName(
                $methodName,
            ),
        );
    }

    #[DataProvider('provideMethodsAndVarsForMethodToVarConversionTests')]
    public function testMethodToVarConversion(string $methodNamingSchema, string $varNamingSchema, string $methodName, string $varName): void
    {
        $converter = new MethodToVariableNameConverter(
            methodNamingSchema: $methodNamingSchema,
            varNamingSchema: $varNamingSchema,
            isserPrefix: true,
        );

        self::assertSame(
            $varName,
            $converter->convertMethodToVariableName($methodName),
        );
    }

    #[DataProvider('provideVarsForSupportTests')]
    public function testSupportedVars(string $namingSchema, string $varName, bool $isValid): void
    {
        $converter = new MethodToVariableNameConverter(
            methodNamingSchema: $namingSchema,
            varNamingSchema: $namingSchema,
            isserPrefix: true,
        );

        if (!$isValid) {
            self::assertFalse(
                $converter->isSupportedVariableName(
                    $varName,
                ),
            );

            return;
        }

        self::assertTrue(
            $converter->isSupportedVariableName(
                $varName,
            ),
        );
    }

    #[DataProvider('provideMethodsAndVarsForVarToMethodConversionTests')]
    public function testVarToMethodConversion(string $varNamingSchema, string $methodNamingSchema, string $varName, string $methodName): void
    {
        $converter = new MethodToVariableNameConverter(
            methodNamingSchema: $methodNamingSchema,
            varNamingSchema: $varNamingSchema,
            isserPrefix: true,
        );

        self::assertSame(
            $methodName,
            $converter->convertVariableToMethodName($varName),
        );
    }

    public static function provideMethodsForSupportTests(): Generator
    {
        foreach (self::METHODS as $type => $typeConfig) {
            $namingSchema = $typeConfig['namingSchema'];

            foreach (self::METHODS as $checkedType => $checkedTypeConfig) {
                foreach ($checkedTypeConfig['methods'] as $methodType => $methodConfig) {
                    yield sprintf('%s-%s-%s', $type, $checkedType, $methodType) => [
                        'namingSchema' => $namingSchema,
                        'methodName' => $methodConfig['method'],
                        'isValid' => $checkedTypeConfig['supportsMethods'] && ($type === $checkedType) && $methodConfig['valid'],
                    ];
                }
            }
        }
    }

    public static function provideVarsForSupportTests(): Generator
    {
        foreach (self::METHODS as $type => $typeConfig) {
            $namingSchema = $typeConfig['namingSchema'];

            foreach (self::METHODS as $checkedType => $checkedTypeConfig) {
                yield sprintf('%s-%s', $type, $checkedType) => [
                    'namingSchema' => $namingSchema,
                    'varName' => $checkedTypeConfig['methods']['GETTER']['var'],
                    'isValid' => $type === $checkedType,
                ];
            }
        }
    }

    public static function provideMethodsAndVarsForMethodToVarConversionTests(): Generator
    {
        foreach (self::METHODS as $srcType => $srcTypeConfig) {
            if (!$srcTypeConfig['supportsMethods']) {
                continue;
            }

            $srcNamingSchema = $srcTypeConfig['namingSchema'];

            foreach (self::METHODS as $dstType => $dstTypeConfig) {
                if ($dstType === $srcType) {
                    continue;
                }

                foreach ($dstTypeConfig['methods'] as $methodType => $dstMethodConfig) {
                    if (!$dstMethodConfig['valid']) {
                        continue;
                    }

                    yield sprintf('%s-%s-%s', $dstType, $srcType, $methodType) => [
                        'methodNamingSchema' => $srcNamingSchema,
                        'varNamingSchema' => $dstTypeConfig['namingSchema'],
                        'methodName' => $srcTypeConfig['methods'][$methodType]['method'],
                        'varName' => $dstMethodConfig['var'],
                    ];
                }
            }
        }
    }

    public static function provideMethodsAndVarsForVarToMethodConversionTests(): Generator
    {
        foreach (self::METHODS as $dstType => $dstTypeConfig) {
            $dstNamingSchema = $dstTypeConfig['namingSchema'];

            foreach (self::METHODS as $srcType => $srcTypeConfig) {
                if (!$srcTypeConfig['supportsMethods']) {
                    continue;
                }

                if ($srcType === $dstType) {
                    continue;
                }

                yield sprintf('%s-%s-%s', $dstType, $srcType, 'SETTER') => [
                    'varNamingSchema' => $srcTypeConfig['namingSchema'],
                    'methodNamingSchema' => $dstNamingSchema,
                    'varName' => $srcTypeConfig['methods']['SETTER']['var'],
                    'methodName' => $dstTypeConfig['methods']['SETTER']['method'],
                ];
            }
        }
    }
}
