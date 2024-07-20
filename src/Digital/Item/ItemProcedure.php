<?php

declare(strict_types=1);

namespace Digital\Item;

use Digital\Ast\AstRoot;

Class ItemProcedure implements IItem {
    protected int $type;
    protected string $name;
    protected int $offset;
    protected int $param_count;
    /** = param_count + local_count + return_count */
    protected int $item_count;

    protected string $rawbytes;

    protected AstRoot $ast;

    /**
     * @var AstNode[]
     */
    protected $callingNodes;

    public function __construct($item_type, $func_name, $func_offset, $func_param_count, $item_count) {
        $this->type = $item_type;
        $this->name = $func_name;
        $this->offset = $func_offset;
        $this->param_count = $func_param_count;
        $this->item_count = $item_count;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getType() : int {
        return $this->type;
    }

    public function isValue() : bool {
        return false;
    }

    public function isArray(): bool {
        return false;
    }

    public function isString() : bool {
        return false;
    }

    public function isProcedure() : bool {
        return true;
    }

    public function getOffset() : int {
        return $this->offset;
    }

    public function setRawbytes(string $bytes) {
        $this->rawbytes = $bytes;
    }

    public function setAstRoot(AstRoot $ast) {
        $this->ast = $ast;
    }

    /**
     * @return AstRoot | null
     */
    public function getAstRoot() {
        return empty($this->ast) ? null : $this->ast;
    }

    public function getParamCount() {
        return $this->param_count;
    }

    public function getItemCount() {
        return $this->item_count;
    }

}
