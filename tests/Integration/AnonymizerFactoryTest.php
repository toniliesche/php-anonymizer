<?php

declare(strict_types=1);

namespace Integration;

use Generator;
use PhpAnonymizer\Anonymizer\Anonymizer;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Config\ContainerBuilderConfig;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Traits\ContainerBuilderTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function sprintf;

class AnonymizerFactoryTest extends TestCase
{
    use ContainerBuilderTrait;

    #[DataProvider('provideConfigFiles')]
    public function testCanBuildAnonymizer(string $configFile): void
    {
        $builder = $this->createBuilder(
            configFile: $configFile,
            builderConfig: (new ContainerBuilderConfig())->withCompile(),
        );

        $anonymizer = $builder->get(Anonymizer::class);
        self::assertInstanceOf(Anonymizer::class, $anonymizer);
    }

    public static function provideConfigFiles(): Generator
    {
        $finder = new Finder();
        $finder->in(sprintf('%s/config', FIXTURES_ROOT));

        foreach ($finder as $file) {
            yield [$file->getRealPath()];
        }
    }
}
