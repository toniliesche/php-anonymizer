<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use PhpAnonymizer\Anonymizer\DependencyInjection\AnonymizerExtension;
use PhpAnonymizer\Anonymizer\DependencyInjection\Compiler\SerializerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AnonymizerBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new SerializerCompilerPass(),
        );
    }

    public function getContainerExtension(): Extension
    {
        return new AnonymizerExtension();
    }
}
