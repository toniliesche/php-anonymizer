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
use Webmozart\Assert\Assert;
use function array_slice;
use function method_exists;
use function ucfirst;

final class SetterDataAccess extends AbstractObjectDataAccess
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $getter = 'get' . ucfirst($name);
        $setter = 'set' . ucfirst($name);

        try {
            return method_exists($parent, $getter)
                // @phpstan-ignore-next-line
                && $parent->{$getter}() !== null
                && method_exists($parent, $setter);
        } catch (Error) {
            return false;
        }
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $getter = 'get' . ucfirst($name);

        if (!method_exists($parent, $getter)) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }

        try {
            // @phpstan-ignore-next-line
            return $parent->{$getter}();
        } catch (Error) {
            throw FieldIsNotInitializedException::fromPath($path);
        }
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $setter = 'set' . ucfirst($name);

        if (!method_exists($parent, $setter)) {
            throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
        }

        // @phpstan-ignore-next-line
        $parent->{$setter}($newValue);
    }

    public function parseDataTree(mixed $data, array $path = []): Tree
    {
        if (!$this->supports($data)) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        $tree = new Tree();
        $methods = get_class_methods($data);

        foreach ($methods as $methodName) {
            if (!str_starts_with($methodName, 'get')) {
                continue;
            }

            $nodeName = lcfirst(substr($methodName, 3));
            if (!$this->hasChild($path, $data, $nodeName)) {
                continue;
            }

            $tree->addChildNode($this->parseNode($nodeName, $methodName, $data, [$nodeName]));
        }

        return $tree;
    }

    /**
     * @param string[] $path
     */
    private function parseNode(string $nodeName, string $methodName, mixed $data, array $path = []): ?Node
    {
        /** @var object $data */
        Assert::methodExists($data, $methodName);

        // @phpstan-ignore-next-line
        $value = $data->{$methodName}();

        if (is_string($value)) {
            return $this->createStringNode($nodeName);
        }

        if (is_array($value)) {
            return $this->parseArrayNode($nodeName, $value, $path);
        }

        if (is_object($value)) {
            return $this->parseObjectNode($nodeName, $value, $path);
        }

        return null;
    }

    /**
     * @param array<int, mixed> $data
     * @param string[] $path
     */
    private function parseArrayNode(string $nodeName, array $data, array $path): Node
    {
        if (ArrayTools::detectType($data) !== ArrayType::LIST) {
            throw InvalidObjectTypeException::notAList($path);
        }

        if (ArrayTools::isStringOnlyArray($data)) {
            return new Node(
                name: $nodeName,
                dataAccess: DataAccess::SETTER->value,
                nodeType: NodeType::LEAF,
                isList: true,
            );
        }

        if (ArrayTools::isObjectOnlyArray($data)) {
            return $this->parseObjectListNode($nodeName, $data, $path);
        }

        throw InvalidObjectTypeException::notStringOnlyArray($path);
    }

    private function createStringNode(string $nodeName): Node
    {
        return new Node(
            name: $nodeName,
            dataAccess: DataAccess::SETTER->value,
            nodeType: NodeType::LEAF,
            isList: false,
        );
    }

    /**
     * @param string[] $path
     */
    private function parseObjectNode(string $nodeName, mixed $data, array $path): Node
    {
        $node = new Node(
            name: $nodeName,
            dataAccess: DataAccess::SETTER->value,
            nodeType: NodeType::NODE,
            isList: false,
        );

        $methods = get_class_methods($data);

        foreach ($methods as $methodName) {
            if (!str_starts_with($methodName, 'get')) {
                continue;
            }

            $nodeName = lcfirst(substr($methodName, 3));
            if (!$this->hasChild($path, $data, $nodeName)) {
                continue;
            }

            $tempPath = $path;
            $tempPath[] = $nodeName;
            $node->addChildNode($this->parseNode($nodeName, $methodName, $data, $tempPath));
        }

        return $node;
    }

    /**
     * @param array<int, object> $data
     * @param string[] $path
     */
    private function parseObjectListNode(string $nodeName, array $data, array $path): Node
    {
        $node = new Node(
            name: $nodeName,
            dataAccess: DataAccess::SETTER->value,
            nodeType: NodeType::NODE,
            isList: true,
        );

        foreach ($data as $object) {
            $tempNode = $this->parseObjectNode($nodeName, $object, $path);

            $node->mergeChildrenFromNode($tempNode, $path);
        }

        return $node;
    }
}
