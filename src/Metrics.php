<?php
declare(strict_types=1);

namespace Coveragify;

use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Metrics
{
    public const TYPE_AS_XML = 1;

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

    public static function aggregate(Metrics $metrics): void
    {

    }

    /**
     * @param resource $handle
     */
    public static function reportTo($handle, int $type): void
    {

    }

    public static function reportToFile(string $path, int $type): void
    {
        $handle = fopen($path, 'w+');
        static::reportTo($handle, $type);
        fclose($handle);
    }
}
