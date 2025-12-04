<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleLoader;

use Generator;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\FileNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use function Safe\file_get_contents;
use function Safe\json_decode;
use function sprintf;

final readonly class JsonFileRuleLoader implements RuleLoaderInterface
{
    public function __construct(
        private string $filePath,
        DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if (!$dependencyChecker->extensionIsLoaded('json')) {
            throw new MissingPlatformRequirementsException('The json extension is required for this encoder');
        }
    }

    public function loadRules(): Generator
    {
        if (!file_exists($this->filePath)) {
            throw new FileNotFoundException(sprintf('Json rule file "%s" does not exist.', $this->filePath));
        }

        $json = file_get_contents($this->filePath);
        $data = json_decode($json, true);

        foreach ($data['rules'] as $ruleName => $rule) {
            yield $ruleName => $rule;
        }
    }
}
