<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Override;
use Throwable;
use function Safe\preg_match;

/**
 * @codeCoverageIgnore
 */
final class DateGeneralizeTransformation implements ValueTransformationInterface
{
    #[Override]
    public function id(): string
    {
        return 'date_generalize';
    }

    #[Override]
    public function supportedTypes(): array
    {
        return ['temporal', 'string'];
    }

    #[Override]
    public function outputType(): string
    {
        return 'same';
    }

    /**
     * @param array<string,mixed> $options
     */ #[Override]
    public function validateOptions(array $options): void
    {
        if (!isset($options['unit']) || !is_string($options['unit']) || !in_array($options['unit'], ['year', 'month', 'day', 'hour'], true)) {
            throw new InvalidArgumentException('date_generalize requires unit year, month, day, or hour.');
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    // @phpstan-ignore complexity.functionLike (bounded generalization units)
    #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        $isString = is_string($value);

        try {
            // @phpstan-ignore theCodingMachineSafe.class (guarded conversion)
            $date = $isString ? new DateTimeImmutable($value) : ($value instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($value) : null);
        } catch (Throwable) {
            throw new InvalidArgumentException('date_generalize requires a valid RFC 3339 date or DateTimeInterface.');
        } if (!$date instanceof DateTimeImmutable || ($isString && preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d+)?(?:Z|[+-]\d\d:\d\d)$/D', $value) !== 1)) {
            throw new InvalidArgumentException('date_generalize requires a valid RFC 3339 date or DateTimeInterface.');
        } $unit = $options['unit'];
        if ($unit === 'year') {
            $date = $date->setDate((int) $date->format('Y'), 1, 1)->setTime(0, 0);
        } elseif ($unit === 'month') {
            $date = $date->setDate((int) $date->format('Y'), (int) $date->format('m'), 1)->setTime(0, 0);
        } elseif ($unit === 'day') {
            $date = $date->setTime(0, 0);
        } else {
            $date = $date->setTime((int) $date->format('H'), 0);
        }

return $isString ? $date->format(DateTimeInterface::RFC3339_EXTENDED) : $date;
    }
}
