<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use Error;
use PhpAnonymizer\Anonymizer\Enum\ArrayType;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\FieldIsNotInitializedException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Helpers\ArrayTools;
use PhpAnonymizer\Anonymizer\Model\Data\Node;
use PhpAnonymizer\Anonymizer\Model\Data\Tree;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use function array_slice;

final class ReflectionDataAccess extends AbstractObjectDataAccess
{
    /**
     * @throws ReflectionException
     */
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $reflection = new ReflectionClass($parent);

        if (!$reflection->hasProperty($name)) {
            return false;
        }

        return $this->isValidProperty($reflection->getProperty($name), $parent);
    }

    /**
     * @throws ReflectionException
     */
    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $reflection = new ReflectionClass($parent);
        if (!$reflection->hasProperty($name)) {
            throw FieldDoesNotExistException::fromPath($path);
        }

        $property = $reflection->getProperty($name);
        if (!$property->isInitialized($parent)) {
            throw FieldIsNotInitializedException::fromPath($path);
        }

        return $property->getValue($parent);
    }

    /**
     * @throws ReflectionException
     */
    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $reflection = new ReflectionClass($parent);
        if (!$reflection->hasProperty($name)) {
            throw FieldDoesNotExistException::fromPath($path);
        }

        try {
            $reflection->getProperty($name)->setValue($parent, $newValue);
        } catch (Error) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }
    }

    public function parseDataTree(mixed $data, array $path = []): Tree
    {
        if (!$this->supports($data)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $tree = new Tree();

        $reflection = new ReflectionClass($data);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if (!$this->isValidProperty($property, $data)) {
                continue;
            }

            $tree->addChildNode($this->parseDataNode($property->name, $property->getValue($data), [$property->name]));
        }

        return $tree;
    }

    /**
     * @param string[] $path
     */
    private function parseDataNode(string $key, mixed $data, array $path): ?Node
    {
        if (is_string($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::REFLECTION->value,
                nodeType: NodeType::LEAF,
                isList: false,
            );
        }

        if (is_array($data)) {
            $arrayType = ArrayTools::detectType($data);

            return match ($arrayType) {
                ArrayType::LIST => $this->parseListNode($key, $data, $path),
                ArrayType::MAP => $this->parseMapNode($key, $data, $path),
                ArrayType::MIXED => throw InvalidObjectTypeException::mixedArray($path),
            };
        }

        return is_object($data) ? $this->parseObjectNode($key, $data, $path) : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $path
     */
    private function parseMapNode(string $key, array $data, array $path): Node
    {
        $childNodes = [];

        foreach ($data as $dataKey => $value) {
            $childNodes[] = $this->parseDataNode($dataKey, $value, $path);
        }

        return new Node(
            name: $key,
            dataAccess: DataAccess::REFLECTION->value,
            nodeType: NodeType::NODE,
            isList: false,
            childNodes: $childNodes,
        );
    }

    /**
     * @param array<int, mixed> $data
     * @param string[] $path
     */
    private function parseListNode(string $key, array $data, array $path): Node
    {
        if (ArrayTools::isObjectOnlyArray($data)) {
            return $this->parseListOfObjects($key, $data, $path);
        }

        if (ArrayTools::isScalarOnlyArray($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::REFLECTION->value,
                nodeType: NodeType::LEAF,
                isList: true,
            );
        }

        throw new RuntimeException();
    }

    /**
     * @param array<int, array<int|string, mixed>> $data
     * @param string[] $path
     */
    private function parseListOfObjects(string $key, array $data, array $path): Node
    {
        $node = new Node(
            name: $key,
            dataAccess: DataAccess::REFLECTION->value,
            nodeType: NodeType::NODE,
            isList: true,
        );

        foreach ($data as $listItem) {
            $node->mergeChildrenFromNode($this->parseDataNode($key, $listItem, $path), $path);
        }

        return $node;
    }

    /**
     * @param string[] $path
     */
    private function parseObjectNode(string $key, mixed $data, array $path): Node
    {
        $node = new Node(
            name: $key,
            dataAccess: DataAccess::REFLECTION->value,
            nodeType: NodeType::NODE,
            isList: false,
        );

        $reflection = new ReflectionClass($data);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if (!$this->isValidProperty($property, $data)) {
                continue;
            }

            $node->addChildNode($this->parseDataNode($property->name, $property->getValue($data), $path + [$property->name]));
        }

        return $node;
    }

    private function isValidProperty(ReflectionProperty $property, mixed $parent): bool
    {
        return $property->isInitialized($parent)
            && $property->getValue($parent) !== null
            && $property->isReadOnly() === false;
    }
}
