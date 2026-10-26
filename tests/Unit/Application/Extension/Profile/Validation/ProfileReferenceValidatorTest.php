<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension\Profile\Validation;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadData;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\DateGeneralizeTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileGlobalRuleParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticContextualReplacementStrategy;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticValueTransformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileReferenceValidator::class)]
#[CoversClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(JsonPayloadData::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(TransformationDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
final class ProfileReferenceValidatorTest extends TestCase
{
    public function testRejectsUnknownStrategyCodecAndTransformationReferences(): void
    {
        $profile = ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [[
            'field' => 'email', 'strategy' => 'missing',
        ]]]);

        try {
            $this->validator()->validate(new ProfileRegistry([$profile]), $this->strategies(), new PayloadCodecRegistry([new JsonPayloadCodec()]));
            self::fail('Expected unknown strategy.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('unknown replacement strategy', $exception->getMessage());
        }

        $embedded = ProfileDefinition::fromArray('embedded', ['mode' => 'redact', 'rules' => [[
            'field' => 'payload', 'embedded' => ['codec' => 'missing', 'profile' => 'invalid'],
        ]]]);

        try {
            $this->validator()->validate(new ProfileRegistry([$embedded]), $this->strategies(), new PayloadCodecRegistry([new JsonPayloadCodec()]));
            self::fail('Expected unknown codec.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('unknown payload codec', $exception->getMessage());
        }
    }

    public function testRejectsUnknownTransformationAndContextKeyMisconfiguration(): void
    {
        $transformed = ProfileDefinition::fromArray('transformed', ['mode' => 'redact', 'rules' => [[
            'field' => 'email', 'transform' => ['type' => 'missing'],
        ]]]);

        try {
            $this->validator()->validate(new ProfileRegistry([$transformed]), $this->strategies(), new PayloadCodecRegistry([new JsonPayloadCodec()]), new TransformationRegistry([new StaticValueTransformation('known')]));
            self::fail('Expected unknown transformation.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('unknown value transformation', $exception->getMessage());
        }

        $invalidOptions = ProfileDefinition::fromArray('invalid-options', ['mode' => 'redact', 'rules' => [[
            'field' => 'date', 'transform' => ['type' => 'date_generalize', 'options' => ['unit' => 'minute']],
        ]]]);

        try {
            $this->validator()->validate(new ProfileRegistry([$invalidOptions]), $this->strategies(), new PayloadCodecRegistry([new JsonPayloadCodec()]), new TransformationRegistry([new DateGeneralizeTransformation()]));
            self::fail('Expected invalid transformation options.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('date_generalize requires unit', $exception->getMessage());
        }

        $contextual = ProfileDefinition::fromArray('contextual', ['mode' => 'redact', 'rules' => [[
            'field' => 'email', 'strategy' => 'contextual',
        ]]]);

        try {
            $this->validator()->validate(new ProfileRegistry([$contextual]), new ReplacementStrategyRegistry([new StaticContextualReplacementStrategy()]), new PayloadCodecRegistry([new JsonPayloadCodec()]));
            self::fail('Expected contextual key requirement.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('must configure key_id', $exception->getMessage());
        }
    }

    public function testTransformationValidationWithoutRegistryFailsAsConfigurationError(): void
    {
        $profile = ProfileDefinition::fromArray('transformed', ['mode' => 'redact', 'rules' => [[
            'field' => 'email', 'transform' => ['type' => 'missing'],
        ]]]);

        try {
            $this->validator()->validate(
                new ProfileRegistry([$profile]),
                $this->strategies(),
                new PayloadCodecRegistry([new JsonPayloadCodec()]),
            );
            self::fail('Expected absent transformation registry to reject the configured transformation.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('unknown value transformation "missing"', $exception->getMessage());
        }
    }

    public function testValidatesStrategyReferencesInsideBothNestedRuleKinds(): void
    {
        $profiles = new ProfileRegistry([
            ProfileDefinition::fromArray('children', ['mode' => 'redact', 'rules' => [[
                'field' => 'address',
                'children' => [['field' => 'email', 'strategy' => 'missing.children']],
            ]]]),
        ]);

        try {
            $this->validator()->validate($profiles, $this->strategies(), new PayloadCodecRegistry([new JsonPayloadCodec()]));
            self::fail('Expected unknown strategy in children.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('missing.children', $exception->getMessage());
        }

        $profiles = new ProfileRegistry([
            ProfileDefinition::fromArray('each', ['mode' => 'redact', 'rules' => [[
                'field' => 'contacts',
                'each' => [['field' => 'email', 'strategy' => 'missing.each']],
            ]]]),
        ]);

        try {
            $this->validator()->validate($profiles, $this->strategies(), new PayloadCodecRegistry([new JsonPayloadCodec()]));
            self::fail('Expected unknown strategy in each.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('missing.each', $exception->getMessage());
        }
    }

    public function testValidatesReferencesAndOptionsOnGlobalRules(): void
    {
        $cases = [
            [
                ProfileDefinition::fromArray('global-strategy', ['mode' => 'redact', 'global_rules' => [['field' => 'email', 'strategy' => 'missing']]]),
                $this->strategies(),
                new TransformationRegistry([]),
                'unknown replacement strategy',
            ],
            [
                ProfileDefinition::fromArray('global-transformation', ['mode' => 'redact', 'global_rules' => [['field' => 'email', 'transform' => ['type' => 'missing']]]]),
                $this->strategies(),
                new TransformationRegistry([]),
                'unknown value transformation',
            ],
            [
                ProfileDefinition::fromArray('global-options', ['mode' => 'redact', 'global_rules' => [['field' => 'date', 'transform' => ['type' => 'date_generalize', 'options' => ['unit' => 'minute']]]]]),
                $this->strategies(),
                new TransformationRegistry([new DateGeneralizeTransformation()]),
                'date_generalize requires unit',
            ],
            [
                ProfileDefinition::fromArray('global-key', ['mode' => 'redact', 'global_rules' => [['field' => 'email', 'strategy' => 'contextual']]]),
                new ReplacementStrategyRegistry([new StaticContextualReplacementStrategy()]),
                new TransformationRegistry([]),
                'must configure key_id',
            ],
        ];

        foreach ($cases as [$profile, $strategies, $transformations, $message]) {
            try {
                $this->validator()->validate(
                    new ProfileRegistry([$profile]),
                    $strategies,
                    new PayloadCodecRegistry([new JsonPayloadCodec()]),
                    $transformations,
                );
                self::fail(sprintf('Expected global rule validation to fail with "%s".', $message));
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testRejectsEmbeddedReferenceCycles(): void
    {
        $first = ProfileDefinition::fromArray('first', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'second']]]]);
        $second = ProfileDefinition::fromArray('second', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'first']]]]);

        $this->expectExceptionMessageMatches('/reference cycle detected/');
        new ProfileReferenceValidator()->validate(
            new ProfileRegistry([$first, $second]),
            new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]),
            new PayloadCodecRegistry([new JsonPayloadCodec()]),
        );
    }

    public function testReportsCompleteCyclePathAcrossMultipleProfiles(): void
    {
        $first = ProfileDefinition::fromArray('first', ['mode' => 'redact', 'rules' => [[
            'field' => 'container',
            'children' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'second']]],
        ]]]);
        $second = ProfileDefinition::fromArray('second', ['mode' => 'redact', 'rules' => [[
            'field' => 'items',
            'each' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'third']]],
        ]]]);
        $third = ProfileDefinition::fromArray('third', ['mode' => 'redact', 'rules' => [[
            'field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'first'],
        ]]]);

        try {
            new ProfileReferenceValidator()->validate(
                new ProfileRegistry([$first, $second, $third]),
                new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]),
                new PayloadCodecRegistry([new JsonPayloadCodec()]),
            );
            self::fail('Expected embedded reference cycle.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('first -> second -> third -> first', $exception->getMessage());
        }
    }

    public function testChecksAllEmbeddedReferencesAtOneRuleLevel(): void
    {
        $first = ProfileDefinition::fromArray('first', ['mode' => 'redact', 'rules' => [
            ['field' => 'safe', 'embedded' => ['codec' => 'json', 'profile' => 'second']],
            ['field' => 'cyclic', 'embedded' => ['codec' => 'json', 'profile' => 'third']],
        ]]);
        $second = ProfileDefinition::fromArray('second', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $third = ProfileDefinition::fromArray('third', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'first']]]]);

        $this->expectExceptionMessageMatches('/first -> third -> first/');
        new ProfileReferenceValidator()->validate(
            new ProfileRegistry([$first, $second, $third]),
            new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]),
            new PayloadCodecRegistry([new JsonPayloadCodec()]),
        );
    }

    public function testRejectsUnknownEmbeddedProfile(): void
    {
        $profile = ProfileDefinition::fromArray('outer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'missing']]]]);

        $this->expectException(InvalidArgumentException::class);
        new ProfileReferenceValidator()->validate(
            new ProfileRegistry([$profile]),
            new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]),
            new PayloadCodecRegistry([new JsonPayloadCodec()]),
        );
    }

    private function validator(): ProfileReferenceValidator
    {
        return new ProfileReferenceValidator();
    }

    private function strategies(): ReplacementStrategyRegistry
    {
        return new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]);
    }
}
