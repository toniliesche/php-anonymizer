<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Domain\Rule;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConditionDefinition::class)]
#[CoversClass(InvalidProfileDefinition::class)]
final class ConditionDefinitionTest extends TestCase
{
    public function testMatchesStrictEqualsAndNestedEscapedPaths(): void
    {
        $condition = ConditionDefinition::fromArray([
            'path' => 'metadata/a~1b',
            'equals' => 'person',
        ]);

        self::assertTrue($condition->matches(['metadata' => ['a/b' => 'person']]));
        self::assertFalse($condition->matches(['metadata' => ['a/b' => 1]]));
        self::assertFalse($condition->matches(['metadata' => []]));
    }

    public function testMatchesValuesInList(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => 'kind', 'in' => ['person', 'company']]);

        self::assertTrue($condition->matches(['kind' => 'company']));
        self::assertFalse($condition->matches(['kind' => 'other']));
    }

    public function testMatchesAllSupportedScalarTypesStrictly(): void
    {
        foreach ([null, true, 1, 1.5, 'text'] as $expected) {
            $condition = ConditionDefinition::fromArray(['path' => 'value', 'equals' => $expected]);
            self::assertTrue($condition->matches(['value' => $expected]));
        }
    }

    public function testRejectsExtraConditionKeysAndMultipleOperators(): void
    {
        foreach ([
            ['path' => 'value', 'equals' => 'x', 'extra' => true],
            ['path' => 'value', 'equals' => 'x', 'in' => ['x']],
        ] as $definition) {
            try {
                ConditionDefinition::fromArray($definition);
                self::fail('Expected malformed condition to be rejected.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testMissingAndInvalidPathsDoNotMatch(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => 'metadata/kind', 'equals' => 'person']);

        self::assertFalse($condition->matches(['items' => []]));
        self::assertFalse($condition->matches(['metadata' => 'person']));
        self::assertFalse(ConditionDefinition::fromArray(['path' => 'kind', 'equals' => 'person'])->matches(['kind' => []]));
        self::assertSame(['metadata', 'kind'], $condition->path());
    }

    public function testDoesNotTreatListIndexesAsObjectPaths(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => '0', 'equals' => 'person']);

        self::assertFalse($condition->matches(['person']));
    }

    public function testRejectsInvalidPathAndOperands(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ConditionDefinition::fromArray(['path' => '/kind', 'equals' => ['person']]);
    }

    #[DoesNotPerformAssertions]
    public function testRejectsMalformedConditionValuesAndPaths(): void
    {
        foreach ([
            ['path' => [], 'equals' => 'person'],
            ['path' => '', 'equals' => 'person'],
            ['path' => 'kind/', 'equals' => 'person'],
            ['path' => 'ki~nd', 'equals' => 'person'],
            ['path' => 'kind', 'equals' => []],
            ['path' => 'kind', 'in' => []],
            ['path' => 'kind', 'in' => 'person'],
            ['path' => 'kind', 'in' => [['person']]],
        ] as $definition) {
            try {
                ConditionDefinition::fromArray($definition);
                self::fail('Expected invalid condition definition.');
            } catch (InvalidProfileDefinition) {
            }
        }
    }
}
