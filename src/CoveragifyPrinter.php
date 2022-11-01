<?php
declare(strict_types=1);

namespace Coveragify;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

class CoveragifyPrinter extends Standard
{
    protected function pStmt_TryCatch(\PhpParser\Node\Stmt\TryCatch $node) {
        $lineBreak = $node->getAttribute('lineBreak', true);

        $stmts = null;

        if ($lineBreak) {
            $stmts = $this->pStmts($node->stmts) . $this->nl;
        } else {
            $stmts = $this->pStmts($node->stmts, false);
        }

        return 'try {' . $stmts . '}'
            . ($node->catches ? ' ' . $this->pImplode($node->catches, ' ') : '')
            . ($node->finally !== null ? ' ' . $this->p($node->finally) : '');
    }

    protected function pStmt_Finally(\PhpParser\Node\Stmt\Finally_ $node) {
        $lineBreak = $node->getAttribute('lineBreak', true);

        $stmts = null;

        if ($lineBreak) {
            $stmts = $this->pStmts($node->stmts) . $this->nl;
        } else {
            $stmts = trim($this->pStmts($node->stmts, false));
        }

        return 'finally {' . $stmts . '}';
    }

    protected function pStmts(array $nodes, bool $indent = true) : string {
        if ($indent) {
            $this->indent();
        }

        $result = '';
        foreach ($nodes as $node) {
            $lineBreak = $node->getAttribute('lineBreak', true);
            $nl = $lineBreak ? $this->nl : '';
            $comments = $node->getComments();
            if ($comments) {
                $result .= $nl . $this->pComments($comments);
                if ($node instanceof \PhpParser\Node\Stmt\Nop) {
                    continue;
                }
            }

            $result .= $nl . $this->p($node);
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }
}