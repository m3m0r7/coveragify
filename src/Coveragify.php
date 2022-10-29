<?php
declare(strict_types=1);

namespace Coveragify;

use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Coveragify
{
    const COVERAGE_COLLECTOR_CODE = <<< CODE
    <?php
    \$__coverage_{{identifier}}->cover(__LINE__, {{nodeType}});
    CODE;

    const COVERAGE_STEP_DEFINITION_CODE = <<< CODE
    <?php
    \$__coverage_{{identifier}} = \Coveragify\Metrics::create(__FILE__, __METHOD__ ?? __FUNCTION__, {{max_steps}});
    CODE;

    const COVERAGE_POST_METRICS_CODE = <<< CODE
    <?php
    \Coveragify\Metrics::aggregate(\$__coverage_{{identifier}});
    CODE;

    protected Node|array|null $coverageCollectorCode = null;
    protected Node|array|null $coveragePostMetricsCode = null;

    public static function inspect(string $file, string $identifier = null): Inspector
    {
        $identifier ??= static::generateIdentifier();
        $coveragify = new static($identifier, $file);
        return new Inspector($coveragify->process($coveragify->parse(file_get_contents($file))));
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

    protected function __construct(protected string $identifier, protected string $file)
    {
        $this->coveragePostMetricsCode = $this->parse(static::COVERAGE_POST_METRICS_CODE);
    }



    protected function process(Node|array|null $node, int &$steps = 0): Node|array|null
    {
        if ($node === null) {
            return null;
        }
        if (is_array($node)) {
            $stmts = [];
            foreach ($node as $oneOfNode) {
                $stmts[] = $this->process($oneOfNode);
            }
            return $stmts;
        }

        switch ($nodeTypeClass = get_class($node)) {
            case Node\Stmt\Namespace_::class:
            case Node\Stmt\Class_::class:
                $node->stmts = $this->process($node->stmts);
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
                foreach ($node->stmts as $index => $stmt) {
                    $coverageCollectorCode = $this->parse(static::COVERAGE_COLLECTOR_CODE, [
                        '{{nodeType}}' => get_class($stmt) . "::class",
                    ]);
                    $coverageCollectorCode ??= is_array($this->coverageCollectorCode) ? $coverageCollectorCode : [$coverageCollectorCode];

                    $steps++;
                    $stmts = [...$stmts, $this->process($stmt, $steps), ...$coverageCollectorCode];
                }

                if ($nodeTypeClass === Node\Stmt\ClassMethod::class || $nodeTypeClass === Node\Stmt\Function_::class) {
                    $coverageStepDefinitionCode = $this->parse(static::COVERAGE_STEP_DEFINITION_CODE, [
                        '{{max_steps}}' => $steps,
                    ]);

                    $stmts = [
                        new Node\Stmt\TryCatch(
                            [
                                ...(is_array($coverageStepDefinitionCode) ? $coverageStepDefinitionCode : [$coverageStepDefinitionCode]),
                                ...$stmts
                            ],
                            [],
                            new Node\Stmt\Finally_($this->coveragePostMetricsCode),
                        ),
                    ];
                }

                $node->stmts = $stmts;
                return $node;
        }

        return $node;
    }
}