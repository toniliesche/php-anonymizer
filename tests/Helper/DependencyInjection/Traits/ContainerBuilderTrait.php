<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Traits;

use PhpAnonymizer\Anonymizer\AnonymizerBundle;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\CompilerPass\AllPublicCompilerPass;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Config\ContainerBuilderConfig;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

trait ContainerBuilderTrait
{
    private function createBuilder(
        string $configFile,
        ContainerBuilderConfig $builderConfig = new ContainerBuilderConfig(),
    ): ContainerBuilder {
        $builder = new ContainerBuilder();

        if ($builderConfig->allPublic) {
            $builder->addCompilerPass(
                new AllPublicCompilerPass(),
                PassConfig::TYPE_BEFORE_REMOVING,
            );
        }

        $bundle = new AnonymizerBundle();
        $bundle->build($builder);
        $extension = $bundle->getContainerExtension();
        $builder->registerExtension($extension);
        $builder->loadFromExtension($extension->getAlias());

        if ($builderConfig->container) {
            $builder->set(ContainerInterface::class, $builder);
            $definition = new Definition();
            $definition->setSynthetic(true);
            $builder->setDefinition(ContainerInterface::class, $definition);
        }

        (new YamlFileLoader(
            container: $builder,
            locator: new FileLocator($configFile),
        ))->load($configFile);

        if ($builderConfig->compile) {
            $builder->compile($builderConfig->resolveEnv);
        }

        return $builder;
    }
}
