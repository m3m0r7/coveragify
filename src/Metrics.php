<?php
declare(strict_types=1);

namespace Coveragify;

use Carbon\Carbon;

class Metrics
{
    public const TYPE_AS_XML = 1;

    protected array $covered = [];
    protected static array $aggregated = [];

    public static function create(string $file, string $target, int $maxSteps): static
    {
        return new static($file, $target, $maxSteps);
    }

    public function __construct(protected string $file, protected string $target, protected int $maxSteps)
    {
    }

    public function cover(int $line, int $complexity, ?string $nodeType): void
    {
        $encountered = 0;
        if (isset($this->covered[$line])) {
            $encountered = $this->covered[$line]['encountered'] + 1;
        }
        $this->covered[$line] = ['complexity' => $complexity, 'encountered' => $encountered, 'nodeType' => $nodeType];
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

    public function getMaxSteps(): int
    {
        return $this->maxSteps;
    }

    public static function aggregate(Metrics $metrics): void
    {
        var_dump($metrics->getCovered());
    }
}
