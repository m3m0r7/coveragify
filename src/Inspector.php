<?php
declare(strict_types=1);

namespace Coveragify;

use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Inspector
{
    public function __construct(protected Node|array|null $node)
    {
    }

    public function getNode(): Node|array|null
    {
        return $this->node;
    }

    public function getCoverageableCode(): string
    {
        return preg_replace(
            "/\A\<\?php\n\n/",
            '',
            (new Standard())
                ->prettyPrint($this->node)
        );
    }
}