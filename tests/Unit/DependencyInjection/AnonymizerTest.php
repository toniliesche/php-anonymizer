<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DependencyInjection;

use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Config\ContainerBuilderConfig;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Traits\ContainerBuilderTrait;
use PHPUnit\Framework\TestCase;
use function sprintf;

class AnonymizerTest extends TestCase
{
    use ContainerBuilderTrait;

    public function testCanLoadEmptyConfig(): void
    {
        $builder = $this->createBuilder(
            configFile: sprintf(
                '%s/config/empty.yaml',
                FIXTURES_ROOT,
            ),
            builderConfig: (new ContainerBuilderConfig())->withCompile(),
        );

        self::assertSame(
            [
                'access_provider' => 'default',
                'faker' => false,
                'faker_seed' => null,
                'generation_provider' => 'default',
                'processor_type' => 'default',
            ],
            $builder->getParameter('anonymizer.data_options'),
        );

        self::assertSame(
            [
                'node_mapper' => 'default',
                'node_parser' => 'array',
                'rule_set_parser' => 'array',
            ],
            $builder->getParameter('anonymizer.parser_options'),
        );

        self::assertSame(
            [],
            $builder->getParameter('anonymizer.rules'),
        );

        self::assertSame(
            [
                'enabled' => false,
                'resolvers' => [
                    'attributes' => true,
                    'phpdocs' => true,
                    'reflection' => true,
                ],
                'naming_schemas' => [
                    'method_names' => 'camelCase',
                    'variable_names' => 'camelCase',
                    'with_isser_functions' => true,
                ],
                'encoders' => [
                    'json' => true,
                    'xml' => false,
                    'yaml' => false,
                ],
            ],
            $builder->getParameter('anonymizer.serializer_options'),
        );
    }

    public function testCanLoadCompleteConfig(): void
    {
        $builder = $this->createBuilder(
            sprintf(
                '%s/config/complete.yaml',
                FIXTURES_ROOT,
            ),
            (new ContainerBuilderConfig())->withCompile(),
        );

        self::assertSame(
            [
                'access_provider' => 'default',
                'faker' => false,
                'faker_seed' => 'my-seed-123',
                'generation_provider' => 'default',
                'processor_type' => 'default',
            ],
            $builder->getParameter('anonymizer.data_options'),
        );

        self::assertSame(
            [
                'node_mapper' => 'default',
                'node_parser' => 'array',
                'rule_set_parser' => 'array',
            ],
            $builder->getParameter('anonymizer.parser_options'),
        );

        self::assertSame(
            [],
            $builder->getParameter('anonymizer.rules'),
        );

        self::assertSame(
            [
                'enabled' => true,
                'resolvers' => [
                    'attributes' => true,
                    'phpdocs' => true,
                    'reflection' => true,
                ],
                'naming_schemas' => [
                    'method_names' => 'camelCase',
                    'variable_names' => 'camelCase',
                    'with_isser_functions' => true,
                ],
                'encoders' => [
                    'json' => true,
                    'xml' => true,
                    'yaml' => true,
                ],
            ],
            $builder->getParameter('anonymizer.serializer_options'),
        );
    }
}
