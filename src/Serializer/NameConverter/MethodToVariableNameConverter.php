<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer\NameConverter;

use Jawira\CaseConverter\Convert;
use PhpAnonymizer\Anonymizer\Enum\NamingSchema;
use PhpAnonymizer\Anonymizer\Exception\InvalidNamingSchemaException;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\AdaCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\CamelCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\CobolCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\KebabCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\MacroCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\NameConverterStrategyInterface;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\PascalCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\SnakeCaseStrategy;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\Strategies\TrainCaseStrategy;
use function Safe\preg_match;
use function Safe\preg_replace;
use function sprintf;

final readonly class MethodToVariableNameConverter implements MethodToVariableNameConverterInterface
{
    private NameConverterStrategyInterface $methodNameStrategy;

    private NameConverterStrategyInterface $varNameStrategy;

    private string $methodMatchExpr;

    private string $methodReplaceExpr;

    private string $varMatchExpr;

    public function __construct(
        string $methodNamingSchema,
        string $varNamingSchema,
        private bool $isserPrefix,
    ) {
        $this->methodNameStrategy = $this->resolveNameStrategy($methodNamingSchema);
        $this->varNameStrategy = $this->resolveNameStrategy($varNamingSchema);

        $this->methodMatchExpr = $this->buildMethodMatchExpression();
        $this->methodReplaceExpr = $this->buildMethodReplaceExpression();
        $this->varMatchExpr = $this->buildVarMatchExpression();
    }

    public function isSupportedMethodName(string $methodName): bool
    {
        return $this->methodNameStrategy->isSupportedInMethodNames()
            && preg_match($this->methodMatchExpr, $methodName) !== 0;
    }

    public function convertMethodToVariableName(string $methodName): string
    {
        $methodName = preg_replace($this->methodReplaceExpr, '', $methodName);

        return $this->varNameStrategy->join($this->methodNameStrategy->split(new Convert($methodName)));
    }

    public function isSupportedVariableName(string $varName): bool
    {
        return preg_match($this->varMatchExpr, $varName) !== 0;
    }

    public function convertVariableToMethodName(string $varName): string
    {
        $varName = $this->methodNameStrategy->join($this->varNameStrategy->split(new Convert($varName)));

        if ($this->methodNameStrategy->isMixedCase()) {
            $varName = ucfirst($varName);
        }

        return sprintf(
            '%s%s%s',
            $this->methodNameStrategy->getSetterPrefix(),
            $this->methodNameStrategy->getSeparator(),
            $varName,
        );
    }

    private function resolveNameStrategy(string $namingSchema): NameConverterStrategyInterface
    {
        return match ($namingSchema) {
            NamingSchema::ADA_CASE->value => new AdaCaseStrategy(),
            NamingSchema::CAMEL_CASE->value => new CamelCaseStrategy(),
            NamingSchema::COBOL_CASE->value => new CobolCaseStrategy(),
            NamingSchema::KEBAB_CASE->value => new KebabCaseStrategy(),
            NamingSchema::MACRO_CASE->value => new MacroCaseStrategy(),
            NamingSchema::PASCAL_CASE->value => new PascalCaseStrategy(),
            NamingSchema::SNAKE_CASE->value => new SnakeCaseStrategy(),
            NamingSchema::TRAIN_CASE->value => new TrainCaseStrategy(),
            default => throw new InvalidNamingSchemaException(sprintf('Given naming schema "%s" is not supported', $namingSchema)),
        };
    }

    private function buildMethodMatchExpression(): string
    {
        $matchExpression = sprintf(
            '/^(:?%s|%s',
            $this->methodNameStrategy->getGetterPrefix(),
            $this->methodNameStrategy->getSetterPrefix(),
        );

        if ($this->isserPrefix) {
            $matchExpression .= sprintf(
                '|%s',
                $this->methodNameStrategy->getIsserPrefix(),
            );
        }

        $matchExpression .= sprintf(
            ')(:?%s%s)+$/',
            $this->methodNameStrategy->getSeparator(),
            $this->methodNameStrategy->getGroupMatch(),
        );

        return $matchExpression;
    }

    private function buildMethodReplaceExpression(): string
    {
        $matchExpression = sprintf(
            '/^(:?%s|%s',
            $this->methodNameStrategy->getGetterPrefix(),
            $this->methodNameStrategy->getSetterPrefix(),
        );

        if ($this->isserPrefix) {
            $matchExpression .= sprintf(
                '|%s',
                $this->methodNameStrategy->getIsserPrefix(),
            );
        }

        $matchExpression .= ')/';

        return $matchExpression;
    }

    private function buildVarMatchExpression(): string
    {
        return sprintf(
            '/^%s(:?%s%s)*$/',
            $this->varNameStrategy->getFirstGroupMatch(),
            $this->varNameStrategy->getSeparator(),
            $this->varNameStrategy->getGroupMatch(),
        );
    }
}
