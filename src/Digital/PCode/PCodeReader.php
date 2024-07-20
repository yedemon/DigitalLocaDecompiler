<?php

declare(strict_types=1);

namespace Digital\PCode;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\IRCore\IRCore;

use Digital\PCode\Reader\EvalMain1;
use Exception;

Class PCodeReader {
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

    protected static function constOrVar($item_type) {
        if ($item_type == self::TYPE_CONST) return TYPE_CONST;
        if ($item_type == self::TYPE_VAR) return TYPE_VAR;

        return TYPE_UNKNOWN;
    }

    protected static function porfore($item_type) {
        if ($item_type == self::TYPE_PROCEDURE) return TYPE_PROCEDURE;
        if ($item_type == self::TYPE_FUNCTION) return TYPE_FUNCTION;
        if ($item_type == self::TYPE_ONEVENT) return TYPE_ONEVENT;

        return TYPE_UNKNOWN;
    }
    
    protected static function valType2VT($valType) : int {
        if ($valType == self::VAL_BOOL) return VT_BOOL;
        if ($valType == self::VAL_FLOAT) return VT_FLOAT;
        if ($valType == self::VAL_INT) return VT_INT;
        if ($valType == self::VAL_STRING) return VT_STRING;
        if ($valType == self::VAL_UNKNOWN) return VT_UNK;

        return VT_UNK;
    }

    protected static function checkValueType($valType) : bool {
        if ($valType == self::VAL_BOOL || 
            $valType == self::VAL_FLOAT || 
            $valType == self::VAL_INT || 
            $valType == self::VAL_STRING
            ) return true;
        return false;
    }

    protected static function valueType($item_flag) {
        $value_type = $item_flag & 0xFFF;
        return $value_type;
    }

    protected static function is_array($item_flag) {
        return !!($item_flag & 0x2000);
    }
    protected static function is_string($item_flag) {
        return !!($item_flag & 0x100);
    }

    public static function getValueTypeStr($valType) : string {
        if ($valType == self::VAL_BOOL) return VAL_BOOL_STR;
        if ($valType == self::VAL_FLOAT) return VAL_FLOAT_STR;
        if ($valType == self::VAL_INT) return VAL_INT_STR;
        if ($valType == self::VAL_STRING) return VAL_STRING_STR;
        if ($valType == self::VAL_UNKNOWN) return VAL_UNKNOWN_STR;

        return VAL_UNKNOWN_STR;
    }

    public static function valTypeToVT($valType) : int {
        if ($valType == self::VAL_BOOL) return VT_BOOL;
        if ($valType == self::VAL_FLOAT) return VT_FLOAT;
        if ($valType == self::VAL_INT) return VT_INT;
        if ($valType == self::VAL_STRING) return VT_STRING;
        if ($valType == self::VAL_UNKNOWN) return VT_UNK;

        return VT_UNK;
    }

    /**
     * @param resource $fp
     */
    public static function predecode($fp, $filesize) {
        // check the first byte. 0x30 + filelen.
        $startByte = freadu1($fp);//r_u1($bytes, 0);
        $blockLenByte = freadu4($fp);//r_u4($bytes, 4);

        if ($startByte == 0x30 && $blockLenByte + 5 == $filesize) {
            // $this->has_0x30 = true;
            // $this->base_offset = 5;
            $base_offset = 5;
        } else {
            // $this->has_0x30 = false;
            // $this->base_offset = 0;
            $base_offset = 0;
            fseek($fp, 0);
        }

        return $base_offset;
        // return static::makeIRCore(fread($fp, $filesize - $base_offset), $base_offset);
    }

    /**
     * not involve cast/score in at the moment.
     * @param string|null $bytes without 0x30 and length (5bytes)
     * @param PCodeDebugger|null $debugger
     */
    public static function makeIRCore($bytes, $debugger) : IRCore {
        // read the next 3 * ints.
        $items_offset = r_u4( $bytes, 0 );
        $events_offset = r_u4( $bytes, 4 );
        $final_offset = r_u4( $bytes, 8 );

        // assume the final_offset only have 4bytes. and should be read 0;
        if ($final_offset + 4 != strlen($bytes)) {
            throw new Exception('<PCODE> the final offset doesn\'t point to the last 4 bytes.');
        }
        if ($events_offset>$final_offset || $items_offset>$events_offset) {
            throw new Exception('<PCODE> offset in bad order.');
        }

        $ircore = new IRCore();

        $ircore->items = [];
        // $ircore->item_consts = [];
        // $ircore->item_vars = [];
        $ircore->item_procedures = [];
        // $this->item_functions = [];
        // $this->item_onEventFuncs = [];

        /** @var AstRoot[] */
        $procedure_offsets = [];

        /** @var AstRoot[] */
        $onEventFunc_offsetMap = [];

        // start from var/const/ functions..
        // $items_offset -> counts.
        $itemsCount = r_u4( $bytes, $items_offset );

        $offset = $items_offset+4;
        while (1) {
            $item = static::readItem($bytes, $offset);

            $ircore->items[] = $item;

            if ($item instanceof AstRoot) {
                $item->setIRCore($ircore);

                $ircore->item_procedures[] = $item;
                $procedure_offsets[] = $item->getBaseOffset();

                if ($item->isOnEvent()) {
                    $event_func_offset = $item->getBaseOffset();
                    $onEventFunc_offsetMap[$event_func_offset] = $item;
                }
            }

            // check offset.
            if ($offset >= $events_offset) {
                break;
            }
        }

        if ($itemsCount != count($ircore->items)) {
            throw new Exception('<PCODE> item count mismatch.');
        }

        // event restore.
        $offset = $events_offset;
        $event_flag = r_u4( $bytes, $offset );
        if ($event_flag != 0x10) {
            throw new Exception('<PCODE> event flag mismatch.');
        }

        $offset = $events_offset+4;
        $scriptId = 0;
        while (1) {
            $script_event_offsets = static::readEvent($bytes, $offset);
            for($i=0;$i<16;$i++) {
                $script_event_offset = $script_event_offsets[$i];
                if ($script_event_offset == 0xFFFFFFFF) continue;

                if (!key_exists($script_event_offset, $onEventFunc_offsetMap)) {
                    throw new Exception('<PCODE> event offset not exist at '. $offset);
                }

                $evtFunction = $onEventFunc_offsetMap[$script_event_offset];
                $evtFunction->setEventType($i);
                $evtFunction->setScriptId($scriptId);

                $func_name = $evtFunction->name;
                $pos = strrpos($func_name, '.');
                if ($pos !== false) {
                    $script_name = substr($func_name, 0, $pos);
                    $function_name = substr($func_name, $pos + 1);

                    $evtFunction->setScriptName($script_name);
                    $evtFunction->name = $function_name;
                } else {
                    throw new Exception('<PCODE> no dot evt function at '. $offset);
                }

                static::repairEvtFunction($evtFunction, $i);
            }

            $scriptId = $scriptId + 1;

            // check offset.
            if ($offset >= $final_offset) {
                break;
            }
        }

        // function restore.
        sort($procedure_offsets, SORT_NUMERIC);
        $procedure_offsets[] = $items_offset;
        $procedure_offset_hash = [];

        for($i=0;$i<count($procedure_offsets)-1;$i++) {
            $offset_start = $procedure_offsets[$i];
            $offset_end = $procedure_offsets[$i+1];

            $procedure_offset_hash[$offset_start] = $offset_end;
        }

        // read function body.
        for($i=0;$i<count($ircore->item_procedures);$i++) {
            $_item = $ircore->item_procedures[$i];
            
            if ($debugger) {
                $debugger->onIteratingProcedure($_item, $should_digest);
                if (!$should_digest)
                    continue;
            }

            $_item_offset = $_item->getBaseOffset();
            $_item_offset_end = $procedure_offset_hash[$_item_offset];

            $rawbytes = substr($bytes, $_item_offset, $_item_offset_end-1);

            // $_item->setRawbytes($rawbytes);

            // $ast = ItemReader::decodeFunc($rawbytes, $_item_offset, $this->items, $_item);
            // $root->setBaseOffset($_item_offset);
            // $root->setGlobalItems($ircore->items);
            // $root->setItemProcedure($_item);

            if ($debugger) {
                $debugger->onBeforeDigestProcedure($_item, $_item_offset_end);
            }

            // $_item->digest($rawbytes, 0);
            static::digestAstRoot($_item, $rawbytes);
            // $_item->setAstRoot($root);

            if ($debugger) {
                $debugger->onAfterDigestProcedure($_item);
            }
        }

        // cycle all function invoke.. 
        // see if we can get param types there.
        // and also mark the param ref type.
        for($i=0;$i<count($ircore->item_procedures);$i++) {
            $_item = $ircore->item_procedures[$i];

            if ($debugger) {
                $debugger->onIteratingWalking($_item, $should_walk);
                if (!$should_walk)
                    continue;
            }

            // ★ 
            AstRoot::walk_procedure($_item, function ($pnode, AstNode $node, $results) {
                $fixRefCount = 0;
                $fixValTypeCount = 0;

                if ($results) {
                    foreach ($results as $result) {
                        $fixRefCount += isset($result[0]) ? $result[0] : 0;
                        $fixValTypeCount += isset($result[1]) ? $result[1] : 0;
                    }
                }

                if ($node->type2 == AstFactory::UserCall) {
                    // cycle param blocks.
                    $procedureNode = $node->_object;
                    $params = $node->nodes[0]->nodes;

                    for ($i = 0; $i < count($params); $i++) {
                        $param = $params[$i];
                        $paramItem = $param->nodes[0]->skipTransNode();

                        $isRef = $param->type2 == AstFactory::ParamRef;
                        if ($isRef) {
                            $procedureNode->setParamItemRef($i);
                            $fixRefCount++;
                        }

                        if (isset($paramItem->_valType)) {
                            $procedureNode->setParamItemValType($i, $paramItem->_valType);
                            $fixValTypeCount++;
                        }
                    }
                }

                return [$fixRefCount, $fixValTypeCount];
            });
        }
        
        for($i=0;$i<count($ircore->item_procedures);$i++) {
            $_item = $ircore->item_procedures[$i];
            if ($debugger) {
                $debugger->onFinalIterating($_item);
            }
        }

        return $ircore;
    }

    protected static function repairEvtFunction(AstRoot $evtFunction, $evtType) {
        if ($evtType == EVT_ENTERFRAME ||
            $evtType == EVT_EXITFRAME) {
            $param = $evtFunction->getParamItem(0);
            $param->name = 'Score';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(1);
            $param->name = 'Track';
            $param->_valType = self::VAL_INT;
        }

        else if ($evtType == EVT_CASTCLICK ||
                $evtType == EVT_MOUSEDOWN ||
                $evtType == EVT_MOUSEMOVE ||
                $evtType == EVT_MOUSEUP) {
            $param = $evtFunction->getParamItem(0);
            $param->name = 'Score';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(1);
            $param->name = 'Track';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(2);
            $param->name = 'Button';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(3);
            $param->name = 'X';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(4);
            $param->name = 'Y';
            $param->_valType = self::VAL_INT;
        }

        else if ($evtType == EVT_KEYDOWN ||
                $evtType == EVT_KEYUP) {
            $param = $evtFunction->getParamItem(0);
            $param->name = 'Score';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(1);
            $param->name = 'Track';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(2);
            $param->name = 'Key';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(3);
            $param->name = 'Button';
            $param->_valType = self::VAL_INT;
        }

        else if ($evtType == EVT_KEYPRESS) {
            $param = $evtFunction->getParamItem(0);
            $param->name = 'Score';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(1);
            $param->name = 'Track';
            $param->_valType = self::VAL_INT;

            $param = $evtFunction->getParamItem(2);
            $param->name = 'Key';
            $param->_valType = self::VAL_INT;
        }
    }

    protected static function readItem($bytes, int &$offset) : AstNode {
        $byte0 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        if ($byte0 != 0) {
            throw new Exception('Item not start with 0 at '. $offset);
        }

        $item_type = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));

        if ($item_type == self::TYPE_CONST || 
            $item_type == self::TYPE_VAR) {

            $item_flag = d_u2($bytes, $offset); //upk0('v', substr($bytes, $offset, 2));  $offset += 2;
            $value_type = static::valueType($item_flag); 
            if ( !static::checkValueType($value_type)) {
                throw new Exception('Unknown value_type at '. $offset);
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
                    $array_value = d_var(static::valType2VT($value_type), $bytes, $offset);
                    $array_values[] = $array_value;
                }
            }
            else {
                $item_value = d_var(static::valType2VT($value_type), $bytes, $offset);
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
                // return new ItemValueArr($item_type, $item_name, $value_type, $array_dimen, $array_values);
                return AstFactory::arrayNode(
                    static::constOrVar($item_type),
                    SCOPE_GLOBAL,
                    $item_name, $value_type, $array_values, $array_dimen
                );
            }
            else{
                // return new ItemValue($item_type, $item_name, $value_type, $item_value);
                return AstFactory::valueNode(
                    static::constOrVar($item_type),
                    SCOPE_GLOBAL,
                    $item_name, $value_type, $item_value
                );
            }
        }
        else if ($item_type == self::TYPE_PROCEDURE || 
            $item_type == self::TYPE_FUNCTION || 
            $item_type == self::TYPE_ONEVENT) {

            $func_offset = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
            $func_param_count = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;

            if ($item_type == self::TYPE_FUNCTION) {
                $func_ret_type = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
                $func_param_count = $func_param_count - 1;
            }

            $func_item_count = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;

            $func_name = d_str($bytes, $offset);
            if ($func_name == null) {
                throw new Exception('error read string at '. $offset);
            }

            $byte00 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            if ($byte00 != 0) {
                throw new Exception('Item not end with 0 at '. $offset);
            }

            // these will generate astRoot.. 
            // if ($item_type == IRCore::TYPE_PROCEDURE) {
            //     return new ItemProcedure($item_type, $func_name, $func_offset, $func_param_count, $func_unk0A);
            // } 
            // else if ($item_type == IRCore::TYPE_FUNCTION) {
            //     return new ItemFunction($item_type, $func_name, $func_offset, $func_param_count, $func_ret_type, $func_unk0A);
            // }
            // else if ($item_type == IRCore::TYPE_ONEVENT) {
            //     return new ItemOnEventFunc($item_type, $func_name, $func_offset, $func_param_count, $func_unk0A);
            // }
            $node = AstFactory::procedureNode(static::porfore($item_type), $func_name, $func_offset, 
                    $func_param_count, $func_item_count);
            
            {
                // move from digestAstRoot
                // push return / params as localItems
                if ($item_type == self::TYPE_FUNCTION) {
                    $value_type = static::valueType($func_ret_type);
                    // $localItems[] = //new ItemValue(IRCore::TYPE_VAR, "Return", $value_type, NULL);
                    $node->setReturnItem(
                        AstFactory::valueNode(TYPE_VAR, SCOPE_LOCAL, "Result", $value_type, 0));
                }

                $param_count = $node->getParamCount();
                for ($i = 0; $i < $param_count; $i++) {
                    // $localItems[] = //new ItemValue(IRCore::TYPE_VAR, "Param".$i, IRCore::VAL_UNKNOWN, 0);//??
                    $node->setParamItem( $i, 
                        AstFactory::valueNode(TYPE_VAR, SCOPE_LOCAL, "param".$i, self::VAL_UNKNOWN, 0));
                }
            }

            return $node;
        }
        else {
            throw new Exception('Unknown item type at '. $offset);
        }
    }

    protected static function readEvent($bytes, int &$offset) : array {
        // 64byte per script
        $events = [];

        for ($i = 0; $i < 16; $i++) {
            $events[$i] = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
        }

        return $events;
    }

    private static function digestAstRoot(AstRoot $astRoot, $bytes) {
        // push return / params as localItems
        // if ($astRoot->isFunction()) {
        //     $value_type = static::valueType($astRoot->getRetType());
        //     $localItems[] = //new ItemValue(IRCore::TYPE_VAR, "Return", $value_type, NULL);
        //         AstFactory::valueNode(TYPE_VAR, SCOPE_LOCAL, "Return", $value_type, 0);
        // }

        // $param_count = $astRoot->getParamCount();
        // for ($i = 0; $i < $param_count; $i++) {
        //     $localItems[] = //new ItemValue(IRCore::TYPE_VAR, "Param".$i, IRCore::VAL_UNKNOWN, 0);//??
        //         AstFactory::valueNode(TYPE_VAR, SCOPE_LOCAL, "Param".$i, self::VAL_UNKNOWN, 0);
        // }

        $offset = 0;

        $local_count = d_u4($bytes, $offset); //upk0('V', substr($bytes, $offset, 4)); $offset += 4;
        for ($i=0;$i<$local_count;$i++) {
            // cycle read local. const/var
            $byte0 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            $type = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            // $val_type = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            // $byte3 = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
            $item_flag = d_u2($bytes, $offset); //upk0('v', substr($bytes, $offset, 2));  $offset += 2;

            $val_type = static::valueType($item_flag);
            // $is_string = static::is_string($item_flag);
            $is_array = static::is_array($item_flag);

            if ($is_array) {
                throw new Exception('local array appear at '.$offset);
            }

            if ($type != self::TYPE_CONST && $type != self::TYPE_VAR) {
                throw new Exception('Unknown item type at '.$offset);
            }

            // $val_size = static::valueSize($val_type);
            // if ($val_size == -1 && !$is_string) {
            //     throw new Exception('Unknown val type at '.$offset);
            // }
            if ( !static::checkValueType($val_type) ) {
                throw new Exception('Unknown val type at '.$offset);
            }

            if ($type == self::TYPE_CONST) {
                $_value = d_var(static::valType2VT($val_type), $bytes, $offset);
            }
            else if ($type == self::TYPE_VAR) {
                $_value = 0;
            }
            // $localItems[] = //new ItemValue($type, "Local".$i, $val_type, $_value);
            $astRoot->setLocalItem($i, 
                AstFactory::valueNode(static::constOrVar($type), 
                    SCOPE_LOCAL, "local".$i, $val_type, $_value));
        }

        $astRoot->nodes = EvalMain1::digest($astRoot, $bytes, $offset);
    }
}
