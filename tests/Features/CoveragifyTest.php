<?php
declare(strict_types=1);

namespace Tests\Features;

use Tests\TestCase;

class CoveragifyTest extends TestCase
{
    public function testCollectingPattern1(): void
    {
        $inspector = \Coveragify\Coveragify::inspect(__DIR__ . '/Templates/Template1.php');
        eval($inspector->getCoverageableCode());

        var_dump((new \Test())->outputHelloWorld([]));
    }
}
