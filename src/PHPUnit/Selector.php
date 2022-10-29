<?php
declare(strict_types=1);

namespace Coveragify\PHPUnit;

use Coveragify\CoveragifyDriver;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\Selector as BaseSelector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\NoCodeCoverageDriverAvailableException;
use SebastianBergmann\CodeCoverage\NoCodeCoverageDriverWithPathCoverageSupportAvailableException;

class Selector
{
    public function forLineCoverage(Filter $filter): Driver
    {
        try {
            return (new BaseSelector())->forLineCoverage($filter);
        } catch (NoCodeCoverageDriverAvailableException $e) {
            return new CoveragifyDriver($filter);
        }
    }

    public function forLineAndPathCoverage(Filter $filter): Driver
    {
        try {
            return (new BaseSelector())->forLineAndPathCoverage($filter);
        } catch (NoCodeCoverageDriverWithPathCoverageSupportAvailableException $e) {
            return new CoveragifyDriver($filter);
        }
    }
}