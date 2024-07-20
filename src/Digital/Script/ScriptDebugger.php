<?php

declare(strict_types=1);

namespace Digital\Script;

use Digital\Ast\AstRoot;

Class ScriptDebugger {

    /**
     * @param AstRoot $root
     * @param $should_digest
     */
    public $on_iterating_procedure = null;

    /**
     * @param AstRoot $root
     */
    public $on_final_iterating = null;

    public function onIteratingProcedure(AstRoot $root, &$should_digest) {
        if ($this->on_iterating_procedure) {
            call_user_func_array($this->on_iterating_procedure, [$root, &$should_digest]);
        } else {
            $should_digest = true;
        }
    }

    public function onFinalIterating(AstRoot $root) {
        if ($this->on_final_iterating) {
            call_user_func($this->on_final_iterating, $root);
        }
    }
}