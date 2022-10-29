<?php
declare(strict_types=1);

namespace Coveragify;

use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

class CoveragifyDriver extends Driver
{
    public function __construct(protected Filter $filter)
    {
    }

    public function nameAndVersion(): string
    {
        return 'Coveragify-' . Coveragify::VERSION;
    }

    public function start(): void
    {
        Coveragify::injectIncluding();
    }

    public function stop(): RawCodeCoverageData
    {
        Coveragify::ejectIncluding();

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage(
            Metrics::getAggregations()
        );
    }
}