<?php
declare(strict_types=1);

namespace Tests\Features;

use Tests\TestCase;

class CoveragifyTest extends TestCase
{
    public function testCollectingPattern1(): void
    {
        $inspector = \Coveragify\Coveragify::inspectFile(__DIR__ . '/Templates/Template1.php');
        eval($inspector->getCoveragifyableCode());

        var_dump((new \Test())->outputHelloWorld([]));
    }
}
