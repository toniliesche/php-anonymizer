<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Domain\Rule;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinitionParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConditionDefinition::class)]
#[CoversClass(ConditionDefinitionParser::class)]
#[CoversClass(InvalidProfileDefinition::class)]
#[UsesClass(KeyNormalizer::class)]
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

    public function testMissingValuesNeverMatchNullAndListContextsAreNotTraversed(): void
    {
        $missing = ConditionDefinition::fromArray(['path' => 'metadata/kind', 'equals' => null]);
        $in = ConditionDefinition::fromArray(['path' => 'metadata/kind', 'in' => [null, 'person']]);

        self::assertFalse($missing->matches(['metadata' => []]));
        self::assertFalse($missing->matches(['metadata' => 'not-a-map']));
        self::assertFalse($missing->matches(['metadata' => ['other' => null]]));
        self::assertTrue($missing->matches(['metadata' => ['kind' => null]]));
        self::assertTrue($in->matches(['metadata' => ['kind' => null]]));
        self::assertFalse($in->matches(['metadata' => ['kind' => ['person']]]));
    }

    public function testKeyNormalizationIsOptIn(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => 'first_name', 'equals' => 'Ada']);

        self::assertFalse($condition->matches(['firstName' => 'Ada']));
        self::assertTrue($condition->matches(['firstName' => 'Ada'], true));
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
            } catch (InvalidProfileDefinition $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testRejectsEveryMalformedPathShapeWithExactDiagnostics(): void
    {
        foreach ([
            '' => 'condition path must be a non-empty relative path',
            '/kind' => 'condition path must be a non-empty relative path',
            'kind/' => 'condition path must be a non-empty relative path',
            'kind//value' => 'condition path segment 1 is invalid',
            'kind~2value' => 'condition path segment 0 is invalid',
        ] as $path => $expectedMessage) {
            try {
                ConditionDefinition::fromArray(['path' => $path, 'equals' => 'person']);
                self::fail('Expected malformed path to be rejected.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertSame($expectedMessage, $exception->getMessage());
            }
        }
    }

    public function testInConditionsRemainStrictAcrossScalarTypesAndNormalizedMaps(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => 'account_type', 'in' => [null, false, 1, '1']]);

        self::assertTrue($condition->matches(['accountType' => false], true));
        self::assertTrue($condition->matches(['accountType' => 1], true));
        self::assertFalse($condition->matches(['accountType' => true], true));
        self::assertFalse($condition->matches(['accountType' => ['1']], true));
    }

    public function testNormalizedLookupUsesFirstMatchingMapKeyAndNeverTraversesLists(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => 'first_name', 'equals' => 'Ada']);

        self::assertTrue($condition->matches(['firstName' => 'Ada', 'first-name' => 'Grace'], true));
        self::assertFalse($condition->matches(['items' => [['firstName' => 'Ada']]], true));

        $nullCondition = ConditionDefinition::fromArray(['path' => 'first_name', 'equals' => null]);
        self::assertFalse($nullCondition->matches(['lastName' => null], true));
    }
}
