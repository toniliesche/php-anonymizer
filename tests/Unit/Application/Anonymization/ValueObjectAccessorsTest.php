<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ObjectAnonymizationRequest;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ValueTransformationContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ObjectAnonymizationRequest::class)]
#[CoversClass(ValueTransformationContext::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(JsonPointer::class)]
final class ValueObjectAccessorsTest extends TestCase
{
    public function testObjectRequestExposesImmutableInputAndDefaultsContext(): void
    {
        $object = new stdClass();
        $request = new ObjectAnonymizationRequest('customer', $object, adapterOptions: ['groups' => ['public']]);

        self::assertSame('customer', $request->profile());
        self::assertSame($object, $request->object());
        self::assertSame([], $request->context()->attributes());
        self::assertSame(['groups' => ['public']], $request->adapterOptions());
    }

    public function testTransformationContextExposesPathKeyAndAttributes(): void
    {
        $context = new ValueTransformationContext(
            'customer',
            JsonPointer::fromString('/customer/email'),
            'tenant-key',
            new AnonymizationContext(['tenant' => 'acme']),
        );

        self::assertSame('customer', $context->profile());
        self::assertSame('/customer/email', (string) $context->path());
        self::assertSame('/customer/email', $context->pathString());
        self::assertSame('tenant-key', $context->keyId());
        self::assertSame(['tenant' => 'acme'], $context->attributes());
    }
}
