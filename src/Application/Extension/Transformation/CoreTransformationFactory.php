<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Transformation;

final class CoreTransformationFactory
{
    /**
     * @return list<ValueTransformationInterface>
     */
    public static function defaults(): array
    {
        return [new NullifyTransformation(), new PartialMaskTransformation(), new DateGeneralizeTransformation(), new NumericBucketTransformation()];
    }
}
