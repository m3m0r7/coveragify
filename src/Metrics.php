<?php
declare(strict_types=1);

namespace Coveragify;

use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Metrics
{
    protected array $covered = [];

    public static function create(string $file, string $target, int $maxSteps): static
    {
        return new static($file, $target, $maxSteps);
    }

    public function __construct(protected string $file, protected string $target, protected int $maxSteps)
    {
    }

    public function cover(int $line, ?string $nodeType): void
    {
        $this->covered[$line] = $nodeType;
    }

    public function getCovered(): array
    {
        return $this->covered;
    }
}
