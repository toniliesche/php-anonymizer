<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMode;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileDefinition::class)]
#[CoversClass(RuleDefinition::class)]
#[CoversClass(InvalidProfileDefinition::class)]
#[CoversClass(ConditionDefinition::class)]
#[CoversClass(RuleOptionsCompiler::class)]
#[CoversClass(EmbeddedPayloadDefinition::class)]
final class ProfileDefinitionTest extends TestCase
{
    public function testCreatesAFlatRedactionProfile(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);

        self::assertSame('customer', $profile->name());
        self::assertSame('email', $profile->rules()[0]->field());
        self::assertSame(ProfileMode::REDACT, $profile->mode());
    }

    public function testRejectsInvalidName(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('not valid', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
    }

    public function testRejectsNameWithTrailingNewline(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray("customer\n", ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
    }

    public function testRejectsUnexpectedTopLevelKeys(): void
    {
        $this->expectExceptionMessageMatches('/keys must be exactly mode and rules/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [], 'extra' => true]);
    }

    public function testRejectsUnsupportedMode(): void
    {
        $this->expectExceptionMessageMatches('/mode must be redact or preserve/');
        ProfileDefinition::fromArray('customer', ['mode' => 'unknown', 'rules' => [['field' => 'email']]]);
    }

    public function testRejectsUnsupportedRoot(): void
    {
        $this->expectExceptionMessageMatches('/root must be map or list/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'root' => 'scalar', 'rules' => [['field' => 'email']]]);
    }

    public function testCreatesAPreserveProfile(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email']],
        ]);

        self::assertSame(ProfileMode::PRESERVE, $profile->mode());
    }

    public function testRejectsEmptyOrAssociativeRules(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => ['email' => ['field' => 'email']]]);
    }

    public function testRejectsMissingRules(): void
    {
        $this->expectExceptionMessageMatches('/keys must be exactly mode and rules/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact']);
    }

    public function testRejectsMalformedRule(): void
    {
        $this->expectExceptionMessageMatches('/rules\[0\]/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => '']]]);
    }

    public function testRejectsNonArrayRule(): void
    {
        $this->expectExceptionMessageMatches('/must be a map/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => ['email']]);
    }

    public function testRejectsNonStringField(): void
    {
        $this->expectExceptionMessageMatches('/field must be a non-empty string/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 42]]]);
    }

    public function testRejectsUnknownRuleKeys(): void
    {
        $this->expectExceptionMessageMatches('/rule keys must contain field/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'extra' => true]]]);
    }

    public function testRejectsDuplicateFields(): void
    {
        $this->expectExceptionMessageMatches('/duplicated/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'email']],
        ]);
    }

    public function testCreatesNestedMapAndListRules(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'customer', 'children' => [['field' => 'email'], ['field' => 'name']]],
                ['field' => 'tags', 'each' => true, 'on_scalar' => 'preserve'],
                ['field' => 'orders', 'each' => [['field' => 'reference']], 'on_scalar' => 'redact'],
            ],
        ]);

        self::assertTrue($profile->rules()[0]->hasChildren());
        self::assertFalse($profile->rules()[0]->eachStrings());
        self::assertNull($profile->rules()[0]->eachRules());
        self::assertCount(2, $profile->rules()[0]->children());
        self::assertTrue($profile->rules()[1]->eachStrings());
        self::assertSame('preserve', $profile->rules()[1]->scalarAction()?->value);
        self::assertNull($profile->rules()[1]->eachRules());
        self::assertFalse($profile->rules()[1]->hasChildren());
        self::assertNotNull($profile->rules()[2]->eachRules());
        self::assertSame('redact', $profile->rules()[2]->scalarAction()?->value);
        self::assertFalse($profile->rules()[2]->eachStrings());
        self::assertFalse($profile->rules()[2]->hasChildren());
    }

    public function testRejectsBothTraversalForms(): void
    {
        $this->expectExceptionMessageMatches('/both children and each/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']], 'each' => true]],
        ]);
    }

    public function testRejectsDuplicateNestedSiblingFields(): void
    {
        $this->expectExceptionMessageMatches('/duplicates field "email"/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email'], ['field' => 'email']]]],
        ]);
    }

    public function testRejectsMalformedEachDefinition(): void
    {
        $this->expectExceptionMessageMatches('/each must be true or a non-empty list/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => []]],
        ]);
    }

    public function testRejectsMalformedChildrenDefinition(): void
    {
        $this->expectExceptionMessageMatches('/children must be a non-empty list/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => []]],
        ]);
    }

    public function testRejectsNonMapNestedRule(): void
    {
        $this->expectExceptionMessageMatches('/nested rule 0 must be a map/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => ['email']]],
        ]);
    }

    public function testCompilesConditionsAndScalarAction(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [[
                'field' => 'customer',
                'children' => [['field' => 'email']],
                'when' => ['path' => 'kind', 'in' => ['person', 'company']],
                'on_scalar' => 'preserve',
            ]],
        ]);

        self::assertInstanceOf(ConditionDefinition::class, $profile->rules()[0]->condition());
        self::assertSame('preserve', $profile->rules()[0]->scalarAction()?->value);
    }

    public function testRejectsScalarActionOnLeaf(): void
    {
        $this->expectExceptionMessageMatches('/on_scalar requires children or each/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email', 'on_scalar' => 'preserve']],
        ]);
    }

    public function testRejectsMalformedCondition(): void
    {
        $this->expectExceptionMessageMatches('/condition must contain path and exactly one/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'person', 'in' => ['person']]]],
        ]);
    }

    public function testRejectsNonMapConditionAndInvalidScalarAction(): void
    {
        try {
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact',
                'rules' => [['field' => 'email', 'when' => 'kind']],
            ]);
            self::fail('Expected a condition map validation error.');
        } catch (InvalidProfileDefinition) {
            $this->addToAssertionCount(1);
        }

        $this->expectExceptionMessageMatches('/on_scalar must be redact or preserve/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'mask']],
        ]);
    }

    public function testCompilesStrategyAndEmbeddedPayload(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'email', 'strategy' => 'star_mask'],
                ['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'nested']],
            ],
        ]);

        self::assertSame('star_mask', $profile->rules()[0]->strategy());
        $embedded = $profile->rules()[1]->embedded();
        self::assertInstanceOf(EmbeddedPayloadDefinition::class, $embedded);
        self::assertSame('json', $embedded->codec());
        self::assertSame('nested', $embedded->profile());
    }

    public function testRejectsStrategyAndEmbeddedOnTraversalOrTogether(): void
    {
        try {
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact',
                'rules' => [['field' => 'payload', 'children' => [['field' => 'email']], 'strategy' => 'star_mask']],
            ]);
            self::fail('Expected traversal strategy validation error.');
        } catch (InvalidProfileDefinition) {
            $this->addToAssertionCount(1);
        }

        $this->expectExceptionMessageMatches('/both strategy and embedded/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'payload', 'strategy' => 'star_mask', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]],
        ]);
    }

    public function testRejectsMalformedStrategyAndEmbeddedDefinitions(): void
    {
        try {
            ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 42]]]);
            self::fail('Expected strategy validation error.');
        } catch (InvalidProfileDefinition) {
            $this->addToAssertionCount(1);
        }

        $this->expectExceptionMessageMatches('/embedded must be a map/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json']]]]);
    }

    public function testRejectsInvalidEmbeddedValueTypes(): void
    {
        foreach ([
            ['codec' => 42, 'profile' => 'nested'],
            ['codec' => 'json', 'profile' => 42],
            ['codec' => '', 'profile' => 'nested'],
            ['codec' => 'json', 'profile' => ''],
        ] as $embedded) {
            try {
                ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => $embedded]]]);
                self::fail('Expected invalid embedded value types to be rejected.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertStringContainsString('embedded codec and profile must be non-empty strings', $exception->getMessage());
            }
        }
    }
}
