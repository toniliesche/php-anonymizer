<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

use ReflectionClass;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;

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
