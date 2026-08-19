<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension\Profile\Validation;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileReferenceValidator::class)]
#[CoversClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
final class ProfileReferenceValidatorTest extends TestCase
{
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
}
