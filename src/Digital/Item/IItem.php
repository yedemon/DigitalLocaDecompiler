<?php

declare(strict_types=1);

namespace Digital\Item;

interface IItem {
    const SCOPE_GLOBAL = 0x1;
    const SCOPE_LOCAL = 0x2;

    const TYPE_CONST = 0x81;
    const TYPE_VAR = 0x80;
    const TYPE_PROCEDURE = 0x83;
    const TYPE_FUNCTION = 0x82;
    const TYPE_ONEVENT = 0x84;

    const VAL_BOOL = 0x0B;
    const VAL_FLOAT = 0x05;
    const VAL_INT = 0x03;
    const VAL_STRING = 0x100;
    const VAL_UNKNOWN = 0x0;

    Const EVT_ENTERFRAME = 0;
    Const EVT_CASTCLICK = 1;
    Const EVT_MOUSEDOWN = 2;
    Const EVT_MOUSEMOVE = 3;
    Const EVT_MOUSEUP = 4;
    Const EVT_KEYDOWN = 5;
    Const EVT_KEYPRESS = 6;
    Const EVT_KEYUP = 7;
    Const EVT_EXITFRAME = 8;
    
    public function getType() : int;

    public function isValue() : bool;

    public function isArray() : bool;

    public function isString() : bool;

    public function isProcedure() : bool;
}