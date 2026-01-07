<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Serializer;

use PhpAnonymizer\Anonymizer\Serializer\MethodAwareMetadataFactory;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\MethodToVariableNameConverterInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
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

final class TestClassMetadata implements ClassMetadataInterface
{
    /** @var array<string, AttributeMetadataInterface> */
    private array $attributes = [];

    private ?ClassDiscriminatorMapping $mapping = null;

    public function __construct(
        private readonly string $name,
        #[Ignore]
        private readonly string $secret,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addAttributeMetadata(AttributeMetadataInterface $attributeMetadata): void
    {
        $this->attributes[$attributeMetadata->getName()] = $attributeMetadata;
    }

    /**
     * @return array<string, AttributeMetadataInterface>
     */
    public function getAttributesMetadata(): array
    {
        return $this->attributes;
    }

    public function merge(ClassMetadataInterface $classMetadata): void
    {
        foreach ($classMetadata->getAttributesMetadata() as $name => $attributeMetadata) {
            $this->attributes[$name] = $attributeMetadata;
        }
    }

    /**
     * @return ReflectionClass<TestClassMetadata>
     */
    public function getReflectionClass(): ReflectionClass
    {
        return new ReflectionClass($this);
    }

    public function getClassDiscriminatorMapping(): ?ClassDiscriminatorMapping
    {
        return $this->mapping;
    }

    public function setClassDiscriminatorMapping(?ClassDiscriminatorMapping $mapping): void
    {
        $this->mapping = $mapping;
    }

    #[Ignore]
    public function getSecret(): string
    {
        return $this->secret;
    }
}
