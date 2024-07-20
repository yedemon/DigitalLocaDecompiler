<?php

declare(strict_types=1);

namespace Digital\Item;

Class ItemValue implements IItem {

    public static function getValueTypeStr($valType) : string {
        if ($valType == IItem::VAL_BOOL) return 'BOOL';
        if ($valType == IItem::VAL_FLOAT) return 'FLOAT';
        if ($valType == IItem::VAL_INT) return 'INT';
        if ($valType == IItem::VAL_STRING) return 'STRING';
        if ($valType == IItem::VAL_UNKNOWN) return 'UNK';
    }

    protected int $type;
    protected string $name;
    protected int $valType;
    protected $val;

    public function __construct($item_type, $item_name, $value_type, $item_value) {
        $this->type = $item_type;
        $this->name = $item_name;
        $this->valType = $value_type;
        $this->val = $item_value;
    }

    public function getType() : int {
        return $this->type;
    }

    public function getTypeStr() : string {
        if ($this->type == IItem::TYPE_CONST) return 'CONST';
        if ($this->type == IItem::TYPE_VAR) return 'VAR';
    }

    public function getName() : string {
        return $this->name;
    }

    public function getValueType() : int {
        return $this->valType;
    }

    public function setValueType($valType) {
        $this->valType = $valType;
    }

    // public function getValueTypeStr() : string {
    //     if ($this->valType == IItem::VAL_BOOL) return 'BOOL';
    //     if ($this->valType == IItem::VAL_FLOAT) return 'FLOAT';
    //     if ($this->valType == IItem::VAL_INT) return 'INT';
    //     if ($this->valType == IItem::VAL_STRING) return 'STRING';
    //     if ($this->valType == IItem::VAL_UNKNOWN) return 'UNK';
    // }

    public function getValue() {
        return $this->val;
    }

    public function isValue() : bool {
        return true;
    }   

    public function isProcedure() : bool {
        return false;
    }

    public function isArray() : bool {
        return false;
    }

    public function isString() : bool {
        return ItemReader::is_string($this->valType);
    }
}