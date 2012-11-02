<?php

namespace Webforge\Code\Generator;

use PHPParser_Node_Stmt_Switch;
use PHPParser_Node_Stmt_Case;
use PHPParser_Node_Expr_ClosureUse;
use PHPParser_Node_Expr_Closure;

class PrettyPrinter extends \PHPParser_PrettyPrinter_Zend {

    public function pStmt_Switch(PHPParser_Node_Stmt_Switch $node) {
        return 'switch (' . $this->p($node->cond) . ') {'
             . "\n" . $this->pStmts($node->cases) . "\n" .'}';
    }

    public function pStmt_Case(PHPParser_Node_Stmt_Case $node) {
        return (null !== $node->cond ? 'case ' . $this->p($node->cond) : 'default') . ':'
             . (count($node->stmts) > 0
                ? "\n" . $this->pStmts($node->stmts)
                : ''
               );
    }

    public function pExpr_Closure(PHPParser_Node_Expr_Closure $node) {
        return ($node->static ? 'static ' : '')
             . 'function ' . ($node->byRef ? '&' : '')
             . '(' . $this->pCommaSeparated($node->params) . ')'
             . (!empty($node->uses) ? ' use (' . $this->pCommaSeparated($node->uses) . ')': '')
             . ' {' . "\n" . $this->pStmts($node->stmts) . "\n" . '}';
    }

}
