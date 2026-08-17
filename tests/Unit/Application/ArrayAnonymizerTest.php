<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application;

use PhpAnonymizer\Anonymizer\Application\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(ProfileNotFound::class)]
#[CoversClass(UnsupportedValue::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
final class ArrayAnonymizerTest extends TestCase
{
    private ArrayAnonymizer $anonymizer;

    #[\Override]
    protected function setUp(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'token']],
        ]);
        $this->anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
    }

    public function testRedactsSelectedStringsAndPreservesOtherValues(): void
    {
        $data = ['email' => 'a@example.com', 'token' => '', 'name' => 'Ada', 'age' => 42];

        self::assertSame(
            ['email' => '[REDACTED]', 'token' => '[REDACTED]', 'name' => 'Ada', 'age' => 42],
            $this->anonymizer->anonymize('customer', $data),
        );
    }

    public function testLeavesMissingFieldsUntouched(): void
    {
        self::assertSame(['name' => 'Ada'], $this->anonymizer->anonymize('customer', ['name' => 'Ada']));
    }

    public function testDoesNotMutateInput(): void
    {
        $data = ['email' => 'a@example.com'];
        $this->anonymizer->anonymize('customer', $data);

        // @phpstan-ignore staticMethod.alreadyNarrowedType (documents input immutability)
        self::assertSame('a@example.com', $data['email']);
    }

    public function testReportsUnknownProfile(): void
    {
        $this->expectException(ProfileNotFound::class);
        $this->anonymizer->anonymize('missing', []);
    }

    public function testRejectsSelectedNonStringValues(): void
    {
        $this->expectException(UnsupportedValue::class);
        $this->expectExceptionMessageMatches('/customer/');
        $this->anonymizer->anonymize('customer', ['email' => ['nested' => true]]);
    }
}
