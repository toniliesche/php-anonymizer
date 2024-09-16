<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\Enum\DataAccessProvider;
use PhpAnonymizer\Anonymizer\Exception\DataAccessProviderExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidDataAccessProviderDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataAccessProviderException;

class DefaultDataAccessProviderFactory implements DataAccessProviderFactoryInterface
{
    private const DATA_ACCESS_PROVIDER = [
        DataAccessProvider::DEFAULT->value,
    ];

    /** @var array<string, DataAccessProviderInterface> */
    private array $dataAccessProviders = [];

    /** @var array<string,callable> */
    private array $customDataAccessProviders = [];

    /**
     * @param callable|DataAccessProviderInterface $definition
     */
    public function registerCustomDataAccessProvider(string $name, mixed $definition): void
    {
        if (\in_array($name, self::DATA_ACCESS_PROVIDER, true) || \in_array($name, $this->customDataAccessProviders, true)) {
            throw new DataAccessProviderExistsException(\sprintf('Cannot override existing data access provider: "%s"', $name));
        }

        if ($definition instanceof DataAccessProviderInterface) {
            $this->customDataAccessProviders[$name] = static fn () => $definition;

            return;
        }

        if (!\is_callable($definition)) {
            throw new InvalidDataAccessProviderDefinitionException('Node parser definition must either be a callable or an instance of DataAccessProviderInterface');
        }

        $this->customDataAccessProviders[$name] = $definition;
    }

    public function getDataAccessProvider(?string $type): ?DataAccessProviderInterface
    {
        if ($type === null) {
            return null;
        }

        if (!isset($this->dataAccessProviders[$type])) {
            $this->dataAccessProviders[$type] = $this->resolveDataAccessProvider($type);
        }

        return $this->dataAccessProviders[$type];
    }

    private function resolveDataAccessProvider(string $type): DataAccessProviderInterface
    {
        if (isset($this->customDataAccessProviders[$type])) {
            $dataAccessProvider = $this->customDataAccessProviders[$type]();
            if (!$dataAccessProvider instanceof DataAccessProviderInterface) {
                throw new InvalidDataAccessProviderDefinitionException(\sprintf('Custom data access "%s" provider must implement DataAccessProviderInterface', $type));
            }

            return $dataAccessProvider;
        }

        return match ($type) {
            DataAccessProvider::DEFAULT->value => new DefaultDataAccessProvider(),
            default => throw new UnknownDataAccessProviderException(\sprintf('Unknown data access provider: "%s"', $type)),
        };
    }
}
