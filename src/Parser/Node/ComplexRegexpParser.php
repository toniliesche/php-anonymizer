<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

/**
 * @deprecated - part of deprecated RegexpRuleSetParser
 */
final class ComplexRegexpParser extends AbstractRegexpParser
{
    public function __construct()
    {
        parent::__construct('/^(?<array>\[])?(?<property>[0-9a-zA-Z\.\-_]+)(\[(((?<data_access>[a-z]+)|(#(?<value>[a-zA-Z]+))|(\?(?<nested_type>[a-z]+)\/(?<nested_rule>[a-z_-]+))|(%(?<filter_field>[a-z]+)\/(?<filter_value>[0-9A-Za-z\.\-_]+))))+])?$/');
    }
}
