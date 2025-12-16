<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DependencyInjection;

use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Config\ContainerBuilderConfig;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Traits\ContainerBuilderTrait;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use function sprintf;

final class AnonymizerTest extends TestCase
{
    use ContainerBuilderTrait;
    use MatchesSnapshots;

    public function testCanLoadEmptyConfig(): void
    {
        $builder = $this->createBuilder(
            configFile: sprintf(
                '%s/config/empty.yaml',
                FIXTURES_ROOT,
            ),
            builderConfig: (new ContainerBuilderConfig())->withCompile(),
        );

        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.data_options'));
        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.parser_options'));
        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.rules'));
        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.serializer_options'));
    }

    public function testCanLoadCompleteConfig(): void
    {
        $builder = $this->createBuilder(
            sprintf(
                '%s/config/complete.yaml',
                FIXTURES_ROOT,
            ),
            (new ContainerBuilderConfig())->withCompile(),
        );

        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.data_options'));
        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.parser_options'));
        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.rules'));
        $this->assertMatchesSnapshot($builder->getParameter('anonymizer.serializer_options'));
    }
}
