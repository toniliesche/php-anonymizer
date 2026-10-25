<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileGlobalRuleParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadata;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMode;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileDefinition::class)]
#[CoversClass(RuleDefinition::class)]
#[CoversClass(InvalidProfileDefinition::class)]
#[CoversClass(ConditionDefinition::class)]
#[UsesClass(ConditionDefinitionParser::class)]
#[CoversClass(RuleOptionsCompiler::class)]
#[CoversClass(EmbeddedPayloadDefinitionParser::class)]
#[CoversClass(RuleTraversalCombinationValidator::class)]
#[CoversClass(EmbeddedPayloadDefinition::class)]
#[CoversClass(ProfileDefinitionParser::class)]
#[CoversClass(ProfileGlobalRuleParser::class)]
#[CoversClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileMetadata::class)]
#[CoversClass(ProfileCompositionCompiler::class)]
#[CoversClass(ProfileDefinitionData::class)]
#[CoversClass(ProfileRuleAppender::class)]
#[UsesClass(TransformationDefinition::class)]
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

    public function testKeyNormalizationIsExplicitAndMetadataIsRetainedStructurally(): void
    {
        $default = ProfileDefinition::fromArray('default', [
            'mode' => 'redact', 'rules' => [['field' => 'email']],
        ]);
        self::assertFalse($default->keyNormalization());

        $profile = ProfileDefinition::fromArray('documented', [
            'mode' => 'redact',
            'key_normalization' => true,
            'metadata' => ['owner' => ' team ', 'status' => 'active'],
            'rules' => [['field' => 'email']],
        ]);
        self::assertTrue($profile->keyNormalization());
        $metadata = $profile->metadata();
        self::assertNotNull($metadata);
        self::assertSame(' team ', $metadata->owner());
        self::assertSame('active', $metadata->status());
    }

    public function testRejectsMetadataScalarListAndUnknownKeysWithSpecificMessages(): void
    {
        foreach ([
            ['metadata' => 'owner', 'message' => 'metadata must be a non-empty map'],
            ['metadata' => [], 'message' => 'metadata must be a non-empty map'],
            ['metadata' => ['owner'], 'message' => 'metadata must be a non-empty map'],
            ['metadata' => ['unknown' => 'value'], 'message' => 'metadata contains an unknown key'],
            ['metadata' => ['owner' => '   '], 'message' => 'metadata.owner must be a non-empty string'],
        ] as $case) {
            try {
                ProfileDefinition::fromArray('metadata', [
                    'mode' => 'redact', 'rules' => [['field' => 'email']], 'metadata' => $case['metadata'],
                ]);
                self::fail('Expected invalid metadata.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertStringContainsString($case['message'], $exception->getMessage());
            }
        }
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

    public function testRejectsNonBooleanKeyNormalization(): void
    {
        $this->expectExceptionMessageMatches('/key_normalization must be a boolean/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'key_normalization' => 'true',
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsMalformedAndDuplicateGlobalRules(): void
    {
        $this->expectExceptionMessageMatches('/global_rules\[0\] must be a map/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'global_rules' => [null],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsDuplicateGlobalRuleFields(): void
    {
        $this->expectExceptionMessageMatches('/global_rules\[1\].*duplicated/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'email'], ['field' => 'email']],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsInvalidGlobalRuleDefinition(): void
    {
        $this->expectExceptionMessageMatches('/global_rules\[0\]/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'email', 'children' => []]],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testPreservesEveryValidGlobalRuleInDefinitionOrder(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'global_rules' => [
                ['field' => 'email'],
                ['field' => 'phone'],
            ],
            'rules' => [['field' => 'name']],
        ]);

        self::assertSame(['email', 'phone'], array_map(
            static fn (RuleDefinition $rule): string => $rule->field(),
            $profile->globalRules(),
        ));
    }

    public function testRejectsInvalidFragmentNameAndRules(): void
    {
        $this->expectExceptionMessageMatches('/fragments\[0\]\.name is invalid/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [['name' => '1invalid', 'rules' => [['field' => 'email']]]],
            'rules' => [['use' => '1invalid']],
        ]);
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

    public function testRejectsMultipleLeafActions(): void
    {
        $this->expectExceptionMessageMatches('/more than one of strategy, transform, or embedded/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email', 'strategy' => 'redact', 'transform' => ['type' => 'nullify']]],
        ]);
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

    public function testCompilesTransformationOptions(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [[
                'field' => 'email',
                'transform' => ['type' => 'custom', 'options' => ['mode' => 'strict']],
            ]],
        ]);

        $transformation = $profile->rules()[0]->transformation();
        self::assertNotNull($transformation);
        self::assertSame('custom', $transformation->type());
        self::assertSame(['mode' => 'strict'], $transformation->options());
    }

    public function testCompilesKeyIdsOnlyWithLeafCollaborators(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'email', 'strategy' => 'star_mask', 'key_id' => 'tenant'],
                ['field' => 'name', 'transform' => ['type' => 'custom'], 'key_id' => 'tenant'],
            ],
        ]);

        self::assertSame('tenant', $profile->rules()[0]->keyId());
        self::assertSame('tenant', $profile->rules()[1]->keyId());
    }

    public function testRejectsKeyIdWithoutLeafActionAndInvalidScalarActionValues(): void
    {
        foreach ([
            ['field' => 'email', 'key_id' => 'tenant'],
            ['field' => 'email', 'key_id' => ''],
            ['field' => 'email', 'on_scalar' => 'unknown', 'children' => [['field' => 'value']]],
        ] as $rule) {
            try {
                ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [$rule]]);
                self::fail('Expected invalid rule option.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testRejectsMalformedTransformationOptions(): void
    {
        foreach ([['strict']] as $options) {
            try {
                ProfileDefinition::fromArray('customer', [
                    'mode' => 'redact',
                    'rules' => [['field' => 'email', 'transform' => ['type' => 'custom', 'options' => $options]]],
                ]);
                self::fail('Non-map transformation options must be rejected.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertSame('Profile "customer" is invalid at rules[0]: transform options must be a map.', $exception->getMessage());
            }
        }
    }

    public function testRejectsMalformedTransformationDefinition(): void
    {
        $this->expectExceptionMessageMatches('/transform must be a map with a non-empty type/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email', 'transform' => ['options' => []]]],
        ]);
    }

    public function testRejectsNonStringTransformationTypesAndTraversalLeafActions(): void
    {
        foreach ([
            ['field' => 'email', 'transform' => ['type' => 42]],
            ['field' => 'user', 'children' => [['field' => 'email']], 'transform' => ['type' => 'custom']],
            ['field' => 'user', 'children' => [['field' => 'email']], 'embedded' => ['codec' => 'json', 'profile' => 'nested']],
            ['field' => 'items', 'each' => [['field' => 'email']], 'transform' => ['type' => 'custom']],
        ] as $rule) {
            try {
                ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [$rule]]);
                self::fail('Invalid transformation types and traversal leaf actions must be rejected.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testRejectsAssociativeNestedRuleCollections(): void
    {
        $this->expectExceptionMessageIsOrContains('children must be a non-empty list of rules.');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'user', 'children' => ['email' => ['field' => 'email']]]],
        ]);
    }

    public function testRejectsTransformationOptionsWithNonStringKeys(): void
    {
        $this->expectExceptionMessageMatches('/transform options must be a map with string keys/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email', 'transform' => ['type' => 'custom', 'options' => [0 => 'strict', 'mode' => 'safe']]]],
        ]);
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
