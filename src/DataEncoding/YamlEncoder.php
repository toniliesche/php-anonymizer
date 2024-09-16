<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use Safe\Exceptions\YamlException;
use function Safe\yaml_parse;

class YamlEncoder implements DataEncoderInterface
{
    public function __construct(DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker())
    {
        if (!$dependencyChecker->extensionIsLoaded('yaml')) {
            throw new MissingPlatformRequirementsException('The yaml extension is required for this encoder');
        }
    }

    /**
     * @return array<int|string,mixed>
     */
    public function decode(mixed $data, TempStorage $tempStorage): array
    {
        if (!\is_string($data)) {
            throw new DataEncodingException('YamlEncoder can only decode strings');
        }

        try {
            return yaml_parse($data);
        } catch (YamlException $ex) {
            throw new DataEncodingException(
                message: 'Failed to decode YAML data',
                previous: $ex,
            );
        }
    }

    /**
     * @param array<int|string,mixed> $data
     */
    public function encode(mixed $data, TempStorage $tempStorage): string
    {
        if (!\is_array($data)) {
            throw new DataEncodingException('YamlEncoder can only encode arrays');
        }

        return \yaml_emit($data);
    }

    public function getOverrideDataAccess(): ?string
    {
        return DataAccess::ARRAY->value;
    }

    public function supports(mixed $data): bool
    {
        return \is_string($data);
    }
}
