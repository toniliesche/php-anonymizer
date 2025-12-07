<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Config;

final class ContainerBuilderConfig
{
    public function __construct(
        public bool $allPublic = true,
        public bool $container = true,
        public bool $compile = false,
        public bool $resolveEnv = false,
    ) {
    }

    public function withAllPublic(bool $allPublic = true): self
    {
        $this->allPublic = $allPublic;

        return $this;
    }

    public function withContainer(bool $container = true): self
    {
        $this->container = $container;

        return $this;
    }

    public function withCompile(bool $compile = true): self
    {
        $this->compile = $compile;

        return $this;
    }

    public function withResolveEnv(bool $resolveEnv = true): self
    {
        $this->resolveEnv = $resolveEnv;

        return $this;
    }
}
