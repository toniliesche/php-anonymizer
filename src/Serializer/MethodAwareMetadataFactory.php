<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer;

use PhpAnonymizer\Anonymizer\Serializer\NameConverter\MethodToVariableNameConverterInterface;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final readonly class MethodAwareMetadataFactory implements ClassMetadataFactoryInterface
{
    public function __construct(
        private ClassMetadataFactoryInterface $classMetadataFactory,
        private MethodToVariableNameConverterInterface $methodToVariableNameConverter,
    ) {
    }

    public function getMetadataFor(object|string $value): ClassMetadataInterface
    {
        $metadata = $this->classMetadataFactory->getMetadataFor($value);
        $reflection = new ReflectionClass($metadata);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $publicMethod) {
            if ($publicMethod->getAttributes(Ignore::class) === []) {
                continue;
            }

            if (!$this->methodToVariableNameConverter->isSupportedMethodName($publicMethod->getName())) {
                continue;
            }

            $attributeName = $this->methodToVariableNameConverter->convertMethodToVariableName($publicMethod->getName());

            $attributeMetadata = new AttributeMetadata($attributeName);
            $attributeMetadata->setIgnore(true);
            $metadata->addAttributeMetadata($attributeMetadata);
        }

        return $metadata;
    }

    public function hasMetadataFor(mixed $value): bool
    {
        return $this->classMetadataFactory->hasMetadataFor($value);
    }
}
