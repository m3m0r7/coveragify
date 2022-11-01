<?php
declare(strict_types=1);

namespace Coveragify;

use Coveragify\Stream\Processor;
use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Coveragify
{
    public const VERSION = '0.0.1';

    public const COVERAGE_COLLECTOR_CODE = <<< CODE
    <?php
    \$__coverage_{{identifier}}->enter({{line}});
    CODE;

    public const COVERAGE_STEP_DEFINITION_CODE = <<< CODE
    <?php
    \$__coverage_{{identifier}} = \Coveragify\Metrics::create(__FILE__, __METHOD__ ?? __FUNCTION__, {{coverTargets}});
    CODE;

    public const COVERAGE_POST_METRICS_CODE = <<< CODE
    <?php
    \Coveragify\Metrics::aggregate(\$__coverage_{{identifier}});
    CODE;

    protected Node|array|null $coverageCollectorCode = null;
    protected Node|array|null $coveragePostMetricsCode = null;

    public static function inspectFile(string $file, string $identifier = null): Inspector
    {
        return static::inspect(
            file_get_contents($file),
            $identifier,
            $file
        );
    }

    public static function inspect(string $code, string $identifier = null, string $file = null): Inspector
    {
        $identifier ??= static::generateIdentifier();
        $coveragify = new static($identifier, $file);
        return new Inspector($coveragify->process($coveragify->parse($code)));
    }

    protected function parse(string $code, array $variables = []): Node|array|null
    {
        static $parser = null;
        $parser ??= (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        return $parser->parse(
            str_replace(
                ['{{identifier}}', ...array_keys($variables)],
                [$this->identifier, ...array_values($variables)],
                $code
            )
        );
    }

    protected static function generateIdentifier(): string
    {
        return md5(random_bytes(32));
    }

    protected function __construct(protected string $identifier, protected ?string $file = null)
    {
        $this->coveragePostMetricsCode = $this->parse(static::COVERAGE_POST_METRICS_CODE);
    }

    public static function injectIncluding(): void
    {
        stream_filter_register('coveragifyable', Coveragifyable::class);
    }

    public static function ejectIncluding(): void
    {
    }

    protected function process(Node|array|null $node, int $complexity = 0, array &$coverTargets = []): Node|array|null
    {
        if ($node === null) {
            return null;
        }
        if (is_array($node)) {
            $stmts = [];
            foreach ($node as $oneOfNode) {
                $process = $this->process($oneOfNode, $complexity, $coverTargets);
                $stmts = [...$stmts, ...(is_array($process) ? $process : [$process])];
            }
            return $stmts;
        }

        switch ($nodeTypeClass = get_class($node)) {
            case Node\Stmt\Namespace_::class:
            case Node\Stmt\Class_::class:
                $node->stmts = $this->process($node->stmts, $complexity, $coverTargets);
                return $node;
            case Node\Stmt\ClassMethod::class:
            case Node\Stmt\Function_::class:
            case Node\Stmt\If_::class:
            case Node\Stmt\For_::class:
            case Node\Stmt\Foreach_::class:
            case Node\Stmt\While_::class:
            case Node\Stmt\Do_::class:
            case Node\Stmt\ElseIf_::class:
            case Node\Stmt\Else_::class:
            case Node\Stmt\TryCatch::class:
            case Node\Stmt\Finally_::class:
                $stmts = [];
                foreach ($node->stmts as $stmt) {
                    $coverTargets[] = [$stmt->getStartLine(), get_class($stmt), $complexity];

                    $process = $this->process($stmt, $complexity + 1, $coverTargets);
                    $process = is_array($process) ? $process : [$process];
                    $stmts = [...$stmts, ...$process];
                }

                if ($nodeTypeClass === Node\Stmt\ClassMethod::class || $nodeTypeClass === Node\Stmt\Function_::class) {
                    $coverageStepDefinitionCode = $this->parse(static::COVERAGE_STEP_DEFINITION_CODE, [
                        '{{coverTargets}}' => json_encode($coverTargets),
                    ]);

                    /**
                     * @var Node\Stmt[] $coverageCollectorCode
                     */
                    $coverageStepDefinitionCode ??= (is_array($coverageStepDefinitionCode) ? $coverageStepDefinitionCode : [$coverageStepDefinitionCode]);

                    foreach ($coverageStepDefinitionCode as $code) {
                        $code->setAttribute('lineBreak', false);
                    }

                    $stmts = [
                        $statement = new Node\Stmt\TryCatch(
                            [
                                ...$coverageStepDefinitionCode,
                                ...$stmts
                            ],
                            [],
                            $finally = new Node\Stmt\Finally_($this->coveragePostMetricsCode),
                        ),
                    ];

                    $statement->setAttribute('lineBreak', false);
                    $finally->setAttribute('lineBreak', false);
                }

                $node->stmts = $stmts;
                return $node;
            case Node\Stmt\Switch_::class:
                foreach ($node->cases as &$case) {
                    $case->stmts = $this->process($case->stmts, $complexity + 1, $coverTargets);
                }

                return $node;
            case Node\Stmt\Break_::class:
                return $node;
            case Node\Stmt\Return_::class:
                $node = new Node\Stmt\TryCatch(
                    is_array($node) ? $node : [$node],
                    [],
                    $finally = new Node\Stmt\Finally_($this->coveragePostMetricsCode),
                );
                $node->setAttribute('lineBreak', false);
                $finally->setAttribute('lineBreak', false);
                return $node;
            default:
                $coverTargets[] = [$node->getStartLine(), get_class($node), $complexity];
                $coverageCollectorCode = $this->parse(static::COVERAGE_COLLECTOR_CODE, [
                    '{{line}}' => $node->getStartLine(),
                ]);

                /**
                 * @var Node\Stmt[] $coverageCollectorCode
                 */
                $coverageCollectorCode ??= is_array($this->coverageCollectorCode) ? $coverageCollectorCode : [$coverageCollectorCode];

                foreach ($coverageCollectorCode as $code) {
                    $code->setAttribute('lineBreak', false);
                }

                if (is_array($node)) {
                    return [...$node, ...$coverageCollectorCode];
                }

                return [$node, ...$coverageCollectorCode];
        }

        return $node;
    }
}