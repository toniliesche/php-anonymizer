<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Mapper\Node\Factory;

use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;

interface NodeMapperFactoryInterface
{
    public function getNodeMapper(?string $type): ?NodeMapperInterface;
}
