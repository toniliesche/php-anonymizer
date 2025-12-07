<?php

declare(strict_types=1);

use function Safe\define;

require_once sprintf('%s/vendor/autoload.php', dirname(__DIR__));

const TEST_ROOT = __DIR__;
define('FIXTURES_ROOT', sprintf('%s/Fixtures', TEST_ROOT));
