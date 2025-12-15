<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DependencyInjection;

use PhpAnonymizer\Anonymizer\Enum\DataAccessProvider;
use PhpAnonymizer\Anonymizer\Enum\DataGenerationProvider;
use PhpAnonymizer\Anonymizer\Enum\DataProcessor;
use PhpAnonymizer\Anonymizer\Enum\NamingSchema;
use PhpAnonymizer\Anonymizer\Enum\NodeMapper;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class AnonymizerConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('anonymizer');

        $rootNode = $treeBuilder
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children();

        $this->configureDataAccessors($rootNode);
        $this->configureParsers($rootNode);
        $this->configureSerializer($rootNode);

        $rootNode
            ->variableNode('rules')
            ->defaultValue([])
            ->end();

        return $treeBuilder;
    }

    // @phpstan-ignore-next-line
    private function configureParsers(NodeBuilder $rootNode): void
    {
        $parserNode = $rootNode
            ->arrayNode('parsers')
            ->addDefaultsIfNotSet()
            ->children();

        $parserNode
            ->scalarNode('node_mapper')
            ->defaultValue(NodeMapper::DEFAULT->value)
            ->cannotBeEmpty();

        $parserNode
            ->scalarNode('node_parser')
            ->defaultValue(NodeParser::ARRAY->value)
            ->cannotBeEmpty();

        $parserNode
            ->scalarNode('rule_set_parser')
            ->defaultValue(RuleSetParser::ARRAY->value)
            ->cannotBeEmpty();
    }

    // @phpstan-ignore-next-line
    private function configureDataAccessors(NodeBuilder $rootNode): void
    {
        $dataNode = $rootNode
            ->arrayNode('data')
            ->addDefaultsIfNotSet()
            ->children();

        $dataNode
            ->scalarNode('access_provider')
            ->defaultValue(DataAccessProvider::DEFAULT->value)
            ->cannotBeEmpty();

        $dataNode
            ->booleanNode('faker')
            ->defaultFalse();

        $dataNode
            ->scalarNode('faker_seed')
            ->defaultNull();

        $dataNode
            ->scalarNode('generation_provider')
            ->defaultValue(DataGenerationProvider::DEFAULT->value)
            ->cannotBeEmpty();

        $dataNode
            ->scalarNode('processor_type')
            ->defaultValue(DataProcessor::DEFAULT->value)
            ->cannotBeEmpty();
    }

    // @phpstan-ignore-next-line
    private function configureSerializer(NodeBuilder $rootNode): void
    {
        $serializerNode = $rootNode
            ->arrayNode('serializer')
            ->addDefaultsIfNotSet()
            ->children();

        $serializerNode
            ->booleanNode('enabled')
            ->defaultFalse();

        $resolversNode = $serializerNode
            ->arrayNode('resolvers')
            ->addDefaultsIfNotSet()
            ->children();

        $resolversNode
            ->booleanNode('attributes')
            ->defaultTrue();

        $resolversNode
            ->booleanNode('phpdocs')
            ->defaultTrue();

        $resolversNode
            ->booleanNode('reflection')
            ->defaultTrue();

        $namingSchemasNode = $serializerNode
            ->arrayNode('naming_schemas')
            ->addDefaultsIfNotSet()
            ->children();

        $namingSchemasNode
            ->scalarNode('method_names')
            ->defaultValue(NamingSchema::CAMEL_CASE->value)
            ->cannotBeEmpty();

        $namingSchemasNode
            ->scalarNode('variable_names')
            ->defaultValue(NamingSchema::CAMEL_CASE->value)
            ->cannotBeEmpty();

        $namingSchemasNode
            ->booleanNode('with_isser_functions')
            ->defaultTrue();

        $encodersNode = $serializerNode
            ->arrayNode('encoders')
            ->addDefaultsIfNotSet()
            ->children();

        $encodersNode
            ->booleanNode('json')
            ->defaultTrue();

        $encodersNode
            ->booleanNode('xml')
            ->defaultFalse();

        $encodersNode
            ->booleanNode('yaml')
            ->defaultFalse();
    }
}
