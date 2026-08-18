<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Infrastructure\Profile;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryProfileProvider::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
final class InMemoryProfileProviderTest extends TestCase
{
    public function testReturnsRegisteredProfilesAndNullForUnknownNames(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);

        self::assertSame($profile, $provider->get('customer'));
        self::assertNull($provider->get('missing'));
        self::assertSame([$profile], array_values(iterator_to_array($provider->profiles())));
    }

    public function testRejectsDuplicateProfiles(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);

        $this->expectException(InvalidArgumentException::class);
        new InMemoryProfileProvider([$profile, $profile]);
    }
}
