<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use function sprintf;

final class AnonymizerExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new AnonymizerConfiguration();
        $config = $this->processConfiguration($configuration, $configs);

        /**
         * @var array{
         *   data: array{
         *     access_provider: string,
         *     faker: bool,
         *     faker_seed: ?string,
         *     generation_provider: string,
         *     processor_type: string,
         *   },
         *   parsers: array{
         *     rule_set_parser: string,
         *     node_parser: string,
         *     node_mapper: string,
         *   },
         *   rules: array{
         *
         *   },
         *   serializer: array{
         *     enabled: bool,
         *     encoders: array{
         *       json: bool,
         *       xml: bool,
         *       yaml: bool,
         *     },
         *     naming_schemas: array{
         *       method_names: string,
         *       variable_names: string,
         *      with_isser_functions: bool,
         *     },
         *     resolvers: array{
         *       attributes: bool,
         *       phpdocs: bool,
         *       reflection: bool,
         *     },
         *   }
         * } $config
         */
        $container->setParameter('anonymizer.data_options', $config['data']);
        $container->setParameter('anonymizer.parser_options', $config['parsers']);
        $container->setParameter('anonymizer.rules', $config['rules']);
        $container->setParameter('anonymizer.serializer_options', $config['serializer']);

        $loader = new YamlFileLoader(
            container: $container,
            locator: new FileLocator(sprintf('%s/Resources/config', dirname(__DIR__))),
        );
        $loader->load('./services.yml');
    }
}
