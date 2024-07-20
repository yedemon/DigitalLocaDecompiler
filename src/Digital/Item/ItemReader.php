<?php

declare(strict_types=1);

namespace Digital\Item;

use Exception;

Class ItemReader {
    public static function valueType($item_flag) {
        $value_type = $item_flag & 0xFFF;
        return $value_type;
    }
    public static function valueSize($value_type) {
        if ($value_type == IItem::VAL_BOOL) return 1;
        if ($value_type == IItem::VAL_INT) return 4;
        if ($value_type == IItem::VAL_FLOAT) return 8;

        return -1;
    }

    public static function is_array($item_flag) {
        return !!($item_flag & 0x2000);
    }
    public static function is_string($item_flag) {
        return !!($item_flag & 0x100);
    }

    public static function readItem($bytes, int &$offset) : IItem {
        $byte0 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        if ($byte0 != 0) {
            throw new Exception('Item not start with 0 at '. $offset);
        }
        $item_type = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));

        if ($item_type == IItem::TYPE_CONST || 
            $item_type == IItem::TYPE_VAR) {

            $item_flag = d_u2($bytes, $offset); //upk0('v', substr($bytes, $offset, 2));  $offset += 2;
            $value_type = static::valueType($item_flag); 
            $value_size = static::valueSize($value_type);
            if ($value_size == -1) {
                throw new Exception('Unknown param size at '. $offset);
            }

            $is_array = static::is_array($item_flag);
            $is_string = static::is_string($item_flag);
            if ($is_string) {
                throw new Exception('string const/var at '. $offset);
            }

            if ($is_array) {
                $array_dimen = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;//unsure.
                $array_size = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
                // cycle size;
                $array_values = [];
                for ($i = 0; $i <= $array_size; $i++) {
                    $array_value = d_var($value_type, $bytes, $offset);
                    $array_values[] = $array_value;
                }
            }
            else {
                $item_value = d_var($value_type, $bytes, $offset);
            }

            $item_name = d_str($bytes, $offset);
            if ($item_name == null) {
                throw new Exception('error read string at '. $offset);
            }

            $byte00 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            if ($byte00 != 0) {
                throw new Exception('Item not end with 0 at '. $offset);
            }

            if ($is_array) {
                return new ItemValueArr($item_type, $item_name, $value_type, $array_dimen, $array_values);
            }
            else{
                return new ItemValue($item_type, $item_name, $value_type, $item_value);
            }
        }
        else if ($item_type == IItem::TYPE_PROCEDURE || 
            $item_type == IItem::TYPE_FUNCTION || 
            $item_type == IItem::TYPE_ONEVENT) {

            $func_offset = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
            $func_param_count = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;

            if ($item_type == IItem::TYPE_FUNCTION) {
                $func_ret_type = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
            }

            $func_unk0A = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;

            $func_name = d_str($bytes, $offset);
            if ($func_name == null) {
                throw new Exception('error read string at '. $offset);
            }

            $byte00 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            if ($byte00 != 0) {
                throw new Exception('Item not end with 0 at '. $offset);
            }

            if ($item_type == IItem::TYPE_PROCEDURE) {
                return new ItemProcedure($item_type, $func_name, $func_offset, $func_param_count, $func_unk0A);
            } 
            else if ($item_type == IItem::TYPE_FUNCTION) {
                return new ItemFunction($item_type, $func_name, $func_offset, $func_param_count, $func_ret_type, $func_unk0A);
            }
            else if ($item_type == IItem::TYPE_ONEVENT) {
                return new ItemOnEventFunc($item_type, $func_name, $func_offset, $func_param_count, $func_unk0A);
            }
        }
        else {
            throw new Exception('Unknown item type at '. $offset);
        }
    }

    public static function readEvent($bytes, int &$offset) : array {
        // 64byte per script
        $events = [];

        for ($i = 0; $i < 16; $i++) {
            $events[$i] = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
        }

        return $events;
    }

}
