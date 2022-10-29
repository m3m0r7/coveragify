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
    \$__coverage_collector_{{identifier}}[__LINE__] = [__LINE__, __METHOD__, __CLASS__, {{nodeType}}];
    CODE;

    const COVERAGE_STEP_DEFINITION_CODE = <<< CODE
    <?php
    \$__coverage_step_definition_{{identifier}} = {{max_steps}};
    CODE;

    const COVERAGE_POST_METRICS_CODE = <<< CODE
    <?php
    \Coveragify\Metrics::post(
        '{{identifier}}',
        __LINE__,
        __FILE__, 
        __METHOD__,
        __CLASS__,
        \$__coverage_step_definition_{{identifier}},
        \$__coverage_collector_{{identifier}},
    );
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

    public function process(Node|array|null $node, int &$steps = 0): Node|array|null
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

        switch (get_class($node)) {
            case Node\Stmt\Namespace_::class:
            case Node\Stmt\Class_::class:
                $node->stmts = $this->process($node->stmts);
                return $node;
            case Node\Stmt\ClassMethod::class:
            case Node\Stmt\Function_::class:
                $stmts = [];
                foreach ($node->stmts as $index => $stmt) {

                    $coverageCollectorCode = $this->parse(static::COVERAGE_COLLECTOR_CODE, [
                        '{{nodeType}}' => get_class($stmt) . "::class",
                    ]);

                    $coverageCollectorCode ??= is_array($this->coverageCollectorCode) ? $coverageCollectorCode : [$coverageCollectorCode];

                    switch (get_class($stmt)) {
                        case Node\Stmt\If_::class:
                        case Node\Stmt\For_::class:
                        case Node\Stmt\Foreach_::class:
                        case Node\Stmt\While_::class:
                        case Node\Stmt\Do_::class:
                        case Node\Stmt\ElseIf_::class:
                        case Node\Stmt\Else_::class:
                        case Node\Stmt\Function_::class:
                        case Node\Stmt\TryCatch::class:
                        case Node\Stmt\Finally_::class:
                            $process = $this->process($stmt->stmts, $steps);
                            $stmt->stmts = [
                                ...$coverageCollectorCode,
                                ...(is_array($process) ? $process : [$process]),
                            ];
                            $steps++;
                            break;
                        case Node\Stmt\Switch_::class:
                            $caseCoverageCollectorCode = $this->parse(static::COVERAGE_COLLECTOR_CODE, [
                                '{{nodeType}}' => Node\Stmt\Case_::class . "::class",
                            ]);
                            foreach ($stmt->cases as &$case) {
                                $process = $this->process($case->stmts, $steps);
                                $case->stmts = [
                                    ...$caseCoverageCollectorCode,
                                    ...(is_array($process) ? $process : [$process]),
                                ];
                                $steps++;
                            }
                            break;
                        case Node\Expr\Match_::class:
                            break;
                    }

                    $stmts = [
                        ...$stmts,
                        $stmt,
                        ...$coverageCollectorCode,
                    ];
                    $steps++;
                }

                $coverageStepDefinitionCode = $this->parse(static::COVERAGE_STEP_DEFINITION_CODE, [
                    '{{max_steps}}' => $steps,
                ]);

                $node->stmts = [
                    new Node\Stmt\TryCatch(
                        [
                            ...(is_array($coverageStepDefinitionCode) ? $coverageStepDefinitionCode : [$coverageStepDefinitionCode]),
                            ...$stmts
                        ],
                        [],
                        new Node\Stmt\Finally_($this->coveragePostMetricsCode),
                    ),
                ];

                return $node;
        }

        return $node;
    }
}