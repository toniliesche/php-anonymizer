<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Serializer;

use PhpAnonymizer\Anonymizer\Serializer\MethodAwareMetadataFactory;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\MethodToVariableNameConverterInterface;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\TestClassMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final class MethodAwareMetadataFactoryTest extends TestCase
{
    public function testAddsIgnoredAttributeMetadataForSupportedMethods(): void
    {
        $metadata = new TestClassMetadata('TestClass', 'TestSecret');

        $factory = $this->createMock(ClassMetadataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('getMetadataFor')
            ->with('TestClass')
            ->willReturn($metadata);

        $converter = $this->createMock(MethodToVariableNameConverterInterface::class);
        $converter->expects($this->once())
            ->method('isSupportedMethodName')
            ->with('getSecret')
            ->willReturn(true);
        $converter->expects($this->once())
            ->method('convertMethodToVariableName')
            ->with('getSecret')
            ->willReturn('secret');

        $subject = new MethodAwareMetadataFactory($factory, $converter);

        $result = $subject->getMetadataFor('TestClass');

        self::assertSame($metadata, $result);
        self::assertArrayHasKey('secret', $metadata->getAttributesMetadata());
        self::assertTrue($metadata->getAttributesMetadata()['secret']->isIgnored());
    }

    public function testHasMetadataForDelegatesToInnerFactory(): void
    {
        $factory = $this->createMock(ClassMetadataFactoryInterface::class);
        $factory->expects($this->once())
            ->method('hasMetadataFor')
            ->with('TestClass')
            ->willReturn(true);

        $converter = $this->createMock(MethodToVariableNameConverterInterface::class);

        $subject = new MethodAwareMetadataFactory($factory, $converter);

        self::assertTrue($subject->hasMetadataFor('TestClass'));
    }
}
