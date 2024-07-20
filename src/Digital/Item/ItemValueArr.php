<?php

declare(strict_types=1);

namespace Digital\Item;

Class ItemValueArr extends ItemValue implements IItem {
    protected int $dimen;

    public function __construct($item_type, $item_name, $value_type, $array_dimen, $array_values) {
        $this->type = $item_type;
        $this->name = $item_name;
        $this->valType = $value_type;
        $this->dimen = $array_dimen;
        $this->val = $array_values;
    }

    public function isArray() : bool {
        return true;
    }

}
