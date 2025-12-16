<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use Symfony\Component\Serializer\Serializer;

final readonly class SerializerFactory
{
    /**
     * @param array{
     *   enabled: bool,
     *   encoders: array{
     *     json: bool,
     *     xml: bool,
     *     yaml: bool,
     *   },
     *   naming_schemas: array{
     *     method_names: string,
     *     variable_names: string,
     *     with_isser_functions: bool,
     *   },
     *   resolvers: array{
     *     attributes: bool,
     *     phpdocs: bool,
     *     reflection: bool,
     *   },
     * } $serializerOptions
     */
    public function __construct(
        private array $serializerOptions,
        DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if (!$dependencyChecker->libraryIsInstalled('symfony/serializer')) {
            throw new MissingPlatformRequirementsException('The symfony/serializer package is required for this encoder');
        }
    }

    public function create(): Serializer
    {
        $builder = (new SerializerBuilder())
            ->withVariableNameSchema($this->serializerOptions['naming_schemas']['variable_names'])
            ->withMethodNameSchema($this->serializerOptions['naming_schemas']['method_names']);

        if ($this->serializerOptions['naming_schemas']['with_isser_functions']) {
            $builder->withIsserPrefixSupport();
        }

        if ($this->serializerOptions['encoders']['json']) {
            $builder->withJsonEncoder();
        }

        if ($this->serializerOptions['encoders']['xml']) {
            $builder->withXmlEncoder();
        }

        if ($this->serializerOptions['encoders']['yaml']) {
            $builder->withYamlEncoder();
        }

        if ($this->serializerOptions['resolvers']['attributes']) {
            $builder->withAttributeResolver();
        }

        if ($this->serializerOptions['resolvers']['phpdocs']) {
            $builder->withPhpDocsResolver();
        }

        if ($this->serializerOptions['resolvers']['reflection']) {
            $builder->withReflectionResolver();
        }

        return $builder->build();
    }
}
