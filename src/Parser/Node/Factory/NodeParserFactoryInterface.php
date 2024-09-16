<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node\Factory;

use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;

interface NodeParserFactoryInterface
{
    public function getNodeParser(?string $type): ?NodeParserInterface;
}
