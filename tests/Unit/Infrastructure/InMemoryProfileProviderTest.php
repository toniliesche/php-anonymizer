<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Infrastructure;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Infrastructure\InMemoryProfileProvider;
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
