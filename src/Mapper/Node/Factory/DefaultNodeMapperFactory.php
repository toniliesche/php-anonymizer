<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Mapper\Node\Factory;

use PhpAnonymizer\Anonymizer\Enum\NodeMapper;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeMapperDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\NodeMapperExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownNodeMapperException;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;
use function in_array;
use function is_callable;
use function sprintf;

final class DefaultNodeMapperFactory implements NodeMapperFactoryInterface
{
    private const NODE_MAPPERS = [
        NodeMapper::DEFAULT->value,
    ];

    /** @var array<string,NodeMapperInterface> */
    private array $nodeMappers = [];

    /** @var array<string,callable> */
    private array $customNodeMappers = [];

    /**
     * @param callable|NodeMapperInterface $definition
     */
    public function registerCustomNodeMapper(string $name, mixed $definition): void
    {
        if (in_array($name, self::NODE_MAPPERS, true) || in_array($name, $this->customNodeMappers, true)) {
            throw new NodeMapperExistsException(sprintf('Cannot override existing node Mapper: "%s"', $name));
        }

        if ($definition instanceof NodeMapperInterface) {
            $this->customNodeMappers[$name] = static fn () => $definition;

            return;
        }

        if (!is_callable($definition)) {
            throw new InvalidNodeMapperDefinitionException('Node Mapper definition must either be a callable or an instance of NodeMapperInterface');
        }

        $this->customNodeMappers[$name] = $definition;
    }

    public function getNodeMapper(?string $type): ?NodeMapperInterface
    {
        if ($type === null) {
            return null;
        }

        if (!isset($this->nodeMappers[$type])) {
            $this->nodeMappers[$type] = $this->resolveNodeMapper($type);
        }

        return $this->nodeMappers[$type];
    }

    private function resolveNodeMapper(string $type): NodeMapperInterface
    {
        if (isset($this->customNodeMappers[$type])) {
            $nodeMapper = $this->customNodeMappers[$type]();
            if (!$nodeMapper instanceof NodeMapperInterface) {
                throw new InvalidNodeMapperDefinitionException('Custom node Mapper must implement NodeMapperInterface');
            }

            return $nodeMapper;
        }

        return match ($type) {
            NodeMapper::DEFAULT->value => new DefaultNodeMapper(),
            default => throw new UnknownNodeMapperException(sprintf('Unknown node Mapper: "%s"', $type)),
        };
    }
}
