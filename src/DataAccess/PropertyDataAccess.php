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
use RuntimeException;
use stdClass;
use Webmozart\Assert\Assert;
use function array_slice;
use function property_exists;

final class PropertyDataAccess extends AbstractObjectDataAccess
{
    /**
     * @throws ReflectionException
     */
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        if ($parent instanceof stdClass) {
            return property_exists($parent, $name);
        }

        $reflection = new ReflectionClass($parent);

        return $reflection->hasProperty($name)
            && $reflection->getProperty($name)->isPublic()
            && $reflection->getProperty($name)->isInitialized($parent)
            && $reflection->getProperty($name)->getValue($parent) !== null;
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $reflection = new ReflectionClass($parent);

        if ($reflection->hasProperty($name) && !$reflection->getProperty($name)->isInitialized($parent)) {
            throw FieldIsNotInitializedException::fromPath($path);
        }

        // @phpstan-ignore-next-line
        return $parent->{$name} ?? throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        try {
            // @phpstan-ignore-next-line
            $parent->{$name} = $newValue;
        } catch (Error) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }
    }

    public function parseDataTree(mixed $data, array $path = []): Tree
    {
        if (!$this->supports($data)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        Assert::object($data);

        $tree = new Tree();

        $vars = array_keys(get_object_vars($data));
        foreach ($vars as $name) {
            Assert::propertyExists($data, $name);
            // @phpstan-ignore-next-line
            $value = $data->{$name};
            $tree->addChildNode($this->parseDataNode($name, $value, [$name]));
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
                dataAccess: DataAccess::PROPERTY->value,
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
            dataAccess: DataAccess::PROPERTY->value,
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
                dataAccess: DataAccess::PROPERTY->value,
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
            dataAccess: DataAccess::PROPERTY->value,
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
            dataAccess: DataAccess::PROPERTY->value,
            nodeType: NodeType::NODE,
            isList: false,
        );

        $vars = array_keys(get_object_vars($data));

        foreach ($vars as $name) {
            Assert::propertyExists($data, $name);
            // @phpstan-ignore-next-line
            $value = $data->{$name};

            $tempPath = $path;
            $tempPath[] = $name;
            $node->addChildNode($this->parseDataNode($name, $value, $tempPath));
        }

        return $node;
    }
}
