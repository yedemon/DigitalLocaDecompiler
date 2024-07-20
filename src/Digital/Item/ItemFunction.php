<?php

declare(strict_types=1);

namespace Digital\Item;

Class ItemFunction extends ItemProcedure implements IItem {
    protected int $ret_type;

    public function __construct($item_type, $func_name, $func_offset, $func_param_count, $ret_type, $item_count) {
        $this->type = $item_type;
        $this->name = $func_name;
        $this->offset = $func_offset;
        $this->param_count = $func_param_count;
        $this->ret_type = $ret_type;
        $this->item_count = $item_count;
    }

    public function getRetType() : int {
        return $this->ret_type;
    }

    public function getParamCount() {
        return $this->param_count - 1;
    }
}
