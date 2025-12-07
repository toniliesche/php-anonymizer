<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleLoader;

use Generator;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\FileNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use function Safe\file_get_contents;
use function Safe\yaml_parse;
use function sprintf;

final readonly class YamlFileRuleLoader implements RuleLoaderInterface
{
    public function __construct(
        private string $filePath,
        DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if (!$dependencyChecker->extensionIsLoaded('yaml')) {
            throw new MissingPlatformRequirementsException('The yaml extension is required for this encoder');
        }
    }

    public function loadRules(): Generator
    {
        if (!file_exists($this->filePath)) {
            throw new FileNotFoundException(sprintf('Yaml rule file "%s" does not exist.', $this->filePath));
        }

        $yaml = file_get_contents($this->filePath);
        $data = yaml_parse($yaml);

        foreach ($data['rules'] as $ruleName => $rule) {
            yield $ruleName => $rule;
        }
    }
}
