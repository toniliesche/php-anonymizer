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
    #[Override]
    public function transform(mixed $value, array $options): mixed
    {
        $isString = is_string($value);
        $date = $this->date($value, $isString);
        $date = $this->generalize($date, $options['unit']);

        return $isString ? $date->format(DateTimeInterface::RFC3339_EXTENDED) : $date;
    }

    private function date(mixed $value, bool $isString): DateTimeImmutable
    {
        if ($isString && !is_string($value)) {
            throw new InvalidArgumentException('date_generalize requires a valid RFC 3339 date or DateTimeInterface.');
        }

        try {
            // @phpstan-ignore theCodingMachineSafe.class (guarded conversion)
            $date = $isString ? new DateTimeImmutable($value) : ($value instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($value) : null);
        } catch (Throwable) {
            throw new InvalidArgumentException('date_generalize requires a valid RFC 3339 date or DateTimeInterface.');
        }
        if (!$date instanceof DateTimeImmutable || ($isString && preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d+)?(?:Z|[+-]\d\d:\d\d)$/D', $value) !== 1)) {
            throw new InvalidArgumentException('date_generalize requires a valid RFC 3339 date or DateTimeInterface.');
        }

        return $date;
    }

    private function generalize(DateTimeImmutable $date, mixed $unit): DateTimeImmutable
    {
        return match ($unit) {
            'year' => $date->setDate((int) $date->format('Y'), 1, 1)->setTime(0, 0),
            'month' => $date->setDate((int) $date->format('Y'), (int) $date->format('m'), 1)->setTime(0, 0),
            'day' => $date->setTime(0, 0),
            default => $date->setTime((int) $date->format('H'), 0),
        };
    }
}
