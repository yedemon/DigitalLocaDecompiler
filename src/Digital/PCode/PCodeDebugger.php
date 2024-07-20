<?php

declare(strict_types=1);

namespace Digital\PCode;

use Digital\Ast\AstRoot;

Class PCodeDebugger {

    /**
     * @param AstRoot $root
     * @param $should_digest
     */
    public $on_iterating_procedure = null;

    /**
     * @param AstRoot $root
     * @param $item_offset_end
     */
    public $on_before_digest_procedure = null;

    /**
     * @param AstRoot $root
     */
    public $on_after_digest_procedure = null;

    /**
     * @param AstRoot $root
     */
    public $on_iterating_walking = null;
    
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

    public function onBeforeDigestProcedure(AstRoot $root, $item_offset_end) {
        if ($this->on_before_digest_procedure) {
            call_user_func($this->on_before_digest_procedure, $root, $item_offset_end);
        }
    }

    public function onAfterDigestProcedure(AstRoot $root) {
        if ($this->on_after_digest_procedure) {
            call_user_func($this->on_after_digest_procedure, $root);
        }
    }

    public function onIteratingWalking(AstRoot $root, &$should_walk) {
        if ($this->on_iterating_walking) {
            call_user_func_array($this->on_iterating_walking, [$root, &$should_walk]);
        }
    }

    public function onFinalIterating(AstRoot $root) {
        if ($this->on_final_iterating) {
            call_user_func($this->on_final_iterating, $root);
        }
    }
}