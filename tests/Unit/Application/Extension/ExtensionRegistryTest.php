<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension;

use InvalidArgumentException;
use JsonException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticReplacementStrategy;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticValueTransformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultRedactionStrategy::class)]
#[CoversClass(StarMaskStrategy::class)]
#[CoversClass(JsonPayloadCodec::class)]
#[CoversClass(ReplacementStrategyRegistry::class)]
#[CoversClass(PayloadCodecRegistry::class)]
#[CoversClass(TransformationRegistry::class)]
#[CoversClass(ProfileRegistry::class)]
#[CoversClass(ProfileReferenceValidator::class)]
#[CoversClass(EmbeddedProfileReferenceGraph::class)]
#[CoversClass(PayloadAnonymizationResult::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(AnonymizationResult::class)]
final class ExtensionRegistryTest extends TestCase
{
    public function testBuiltInStrategiesAndCodecWork(): void
    {
        $redact = new DefaultRedactionStrategy();
        self::assertSame('redact', $redact->id());
        self::assertSame('[REDACTED]', $redact->replace('secret'));

        $mask = new StarMaskStrategy();
        self::assertSame('star_mask', $mask->id());
        self::assertSame('***', $mask->replace('äbc'));
        self::assertSame('', $mask->replace(''));

        $codec = new JsonPayloadCodec();
        self::assertSame('json', $codec->id());
        self::assertSame(['email' => 'secret'], $codec->decode('{"email":"secret"}'));
        self::assertSame('{"email":"secret"}', $codec->encode(['email' => 'secret']));
    }

    public function testJsonCodecRejectsMalformedAndNonObjectRoots(): void
    {
        $codec = new JsonPayloadCodec();

        try {
            $codec->decode('{');
            self::fail('Expected malformed JSON to fail.');
        } catch (JsonException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(JsonException::class);
        $codec->decode('[]');
    }

    public function testJsonCodecRejectsScalarRoots(): void
    {
        $this->expectExceptionMessageMatches('/JSON payload root must be an object/');

        new JsonPayloadCodec()->decode('null');
    }

    public function testJsonCodecRejectsEmptyObjectRoots(): void
    {
        $this->expectExceptionMessageMatches('/JSON payload root must be an object/');

        new JsonPayloadCodec()->decode('{}');
    }

    public function testRegistriesExposeValuesAndRejectInvalidDuplicates(): void
    {
        $strategy = new StaticReplacementStrategy('custom', suffix: '!');
        $strategies = new ReplacementStrategyRegistry([$strategy]);
        self::assertSame($strategy, $strategies->get('custom'));
        self::assertSame([$strategy], $strategies->all());

        $codec = new StaticPayloadCodec('custom');
        $codecs = new PayloadCodecRegistry([$codec]);
        self::assertSame($codec, $codecs->get('custom'));
        self::assertNotInstanceOf(PayloadCodecInterface::class, $codecs->get('missing'));
        self::assertSame([$codec], $codecs->all());

        $this->expectExceptionMessageMatches('/registered more than once/');
        new ReplacementStrategyRegistry([$strategy, $strategy]);
    }

    public function testRegistriesRejectInvalidIds(): void
    {
        $strategy = new StaticReplacementStrategy('not valid');
        $this->expectExceptionMessageMatches('/ID .* invalid/');
        new ReplacementStrategyRegistry([$strategy]);
    }

    public function testTransformationRegistryRejectsInvalidIdsAndDuplicates(): void
    {
        $transformation = new StaticValueTransformation('custom');

        $registry = new TransformationRegistry([$transformation]);
        self::assertSame($transformation, $registry->get('custom'));
        self::assertSame([$transformation], $registry->all());

        try {
            new TransformationRegistry([$transformation, $transformation]);
            self::fail('Expected duplicate transformation validation.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $invalid = new StaticValueTransformation('not valid', []);

        $this->expectExceptionMessageMatches('/ID .* invalid/');
        new TransformationRegistry([$invalid]);
    }

    public function testRegistriesRejectIdsWithTrailingNewlines(): void
    {
        $strategy = new StaticReplacementStrategy("custom\n");
        $this->expectException(InvalidArgumentException::class);
        new ReplacementStrategyRegistry([$strategy]);
    }

    public function testCodecRegistryRejectsDuplicatesAndInvalidIds(): void
    {
        $codec = new StaticPayloadCodec('custom', []);

        try {
            new PayloadCodecRegistry([$codec, $codec]);
            self::fail('Expected duplicate codec validation.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
        $invalid = new StaticPayloadCodec('not valid', []);
        $this->expectExceptionMessageMatches('/ID .* invalid/');
        new PayloadCodecRegistry([$invalid]);
    }

    public function testCodecRegistryRejectsIdsWithTrailingNewlines(): void
    {
        $codec = new StaticPayloadCodec("custom\n", []);

        $this->expectException(InvalidArgumentException::class);
        new PayloadCodecRegistry([$codec]);
    }

    public function testProfileRegistryValidatesReferencesAndCycles(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $registry = new ProfileRegistry([$profile]);
        self::assertSame($profile, $registry->get('customer'));
        self::assertSame([$profile], array_values(iterator_to_array($registry->profiles())));
        new ProfileReferenceValidator()->validate($registry, new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]), new PayloadCodecRegistry([new JsonPayloadCodec()]));

        $this->expectExceptionMessageMatches('/unknown replacement strategy/');
        $unknown = ProfileDefinition::fromArray('unknown', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'missing']]]);
        new ProfileReferenceValidator()->validate(new ProfileRegistry([$unknown]), new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]), new PayloadCodecRegistry([new JsonPayloadCodec()]));
    }

    public function testProfileRegistryRejectsUnknownCodecReference(): void
    {
        $profile = ProfileDefinition::fromArray('unknown', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'missing', 'profile' => 'unknown']]]]);
        $this->expectExceptionMessageMatches('/unknown payload codec/');
        new ProfileReferenceValidator()->validate(new ProfileRegistry([$profile]), new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]), new PayloadCodecRegistry([new JsonPayloadCodec()]));
    }

    public function testProfileRegistryRejectsDuplicateAndEmbeddedCycles(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $this->expectExceptionMessageMatches('/registered more than once/');
        new ProfileRegistry([$profile, $profile]);
    }

    public function testPayloadResultExposesPayloadAndReport(): void
    {
        $result = new AnonymizationResult(['email' => '[REDACTED]'], ['/email'], []);
        $payload = new PayloadAnonymizationResult('{"email":"[REDACTED]"}', $result);
        self::assertSame('{"email":"[REDACTED]"}', $payload->payload());
        self::assertSame($result, $payload->anonymizationResult());
    }
}
