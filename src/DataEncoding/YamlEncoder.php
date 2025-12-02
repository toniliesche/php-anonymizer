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
use function is_array;
use function is_string;
use function Safe\yaml_parse;
use function yaml_emit;

final class YamlEncoder implements DataEncoderInterface
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
        if (!is_string($data)) {
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

    public function encode(mixed $data, TempStorage $tempStorage): string
    {
        if (!is_array($data)) {
            throw new DataEncodingException('YamlEncoder can only encode arrays');
        }

        /** @var array<int|string,mixed> $data */
        return yaml_emit($data);
    }

    public function getOverrideDataAccess(): string
    {
        return DataAccess::ARRAY->value;
    }

    public function supports(mixed $data): bool
    {
        return is_string($data);
    }
}
