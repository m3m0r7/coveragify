<?php
declare(strict_types=1);

namespace Coveragify;

use Carbon\Carbon;

class Metrics
{
    public const TYPE_AS_XML = 1;

    protected array $covered = [];
    protected static array $aggregated = [];

    public static function create(string $file, string $target, array $coverageTargets): static
    {
        return new static($file, $target, $coverageTargets);
    }

    public function __construct(protected string $file, protected string $target, protected array $coverageTargets)
    {
    }

    public function enter(int $line): void
    {
        $encountered = 0;
        if (isset($this->covered[$line])) {
            $encountered = $this->covered[$line]['encountered'] + 1;
        }
        $this->covered[$line] = ['encountered' => $encountered];
    }

    public function getCovered(): array
    {
        return $this->covered;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getCoveredTargets(): array
    {
        return $this->coverageTargets;
    }

    public static function aggregate(Metrics $metrics): void
    {
        $coverages = [];

        foreach ($metrics->getCovered() as $line => $attributes) {
            $coverages[$line] = $attributes['encountered'];
        }

        foreach ($metrics->getCoveredTargets() as [$line, $stmt, $complexity]) {
            if (!isset($coverages[$line])) {
                $coverages[$line] = -1;
            }
        }

        natsort($coverages);
        static::$aggregated[$metrics->getFile()] = $coverages;

        var_dump(static::$aggregated);
    }

    public static function getAggregations(): array
    {
        return static::$aggregated;
    }
}
