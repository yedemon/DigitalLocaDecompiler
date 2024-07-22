<?php

declare(strict_types=1);

namespace Digital\Ast;

use Digital\PCode\PCodeReader;
use Digital\PCode\Reader\EvalSystem;

/**
 * Only create AstNode. 
 */
Class AstFactory {
    const Literal = 'literal';
    const Unary = 'unary';
    const Binary = 'binary';
    // const _Array = '_array';
    // const _Value = '_value';

    const Array = 'array';
    const Value = 'value';
    const Procedure = 'procedure';
    const ParamRef = 'ref';
    const ParamVal = 'val';
    const UserCall = 'usercall';

    // const _UserCall = '_usercall';
    const SysCall = 'syscall';
    const Call = 'call';
    const Object = 'object';
    const Indexer = 'indexer';
    const Bindexer = 'bindexer';
    const Proper = 'proper';
    const Subobject = 'subobj';

    const Trans = 'trans';

    const Assign = 'assign';

    const BLOCK = 'block';//statements..
    const EMPTY = 'empty';//empty..

    const IF = 'if';    //<IF>-<NODE>-<BLOCK>-<BLOCK>

    const CASE = 'case';  //<CASE>-<CASEBRANCH>-<CASEBRANCH>
    const CASEBRANCH = 'casebranch';    //<CASEBRANCH>-<CASECOND>-<BLOCK>
    const CASECOND = 'casecond';

    const FOR = 'for';  //<FOR>-<FORCOND>-<BLOCK>
    const FORCOND = 'forcond';

    const XCall = 'XCall';

    const TRANS_EVAL_ENTRY = 1;
    const TRANS_EVAL_MAIN1 = 2;
    const TRANS_EVAL_SYSTEM = 3;

    const OBJ_TrackProperty = 'TrackProperty';
    const OBJ_JoyStick = 'JoyStick';
    const OBJ_ModelCast = 'ModelCast';
    const OBJ_WaveCast = 'WaveCast';
    const OBJ_CameraCast = 'CameraCast';
    const OBJ_LightCast = 'LightCast';
    const OBJ_AVIFile = 'AVIFile';

    /**
     * Please pretend you do not see this..
     * 用于连接和过度性质的节点, 用于存储一些过剩指令
     * 消除 交给arrayIdxrNode，valueNode 的pcode
     * 不过目前可能需要把EvalSystem的X46/X47 分出去，因为X46/X47 表示从获取值。
     * 因此可以根据X46/X47的trans标识来推断一个函数调用是否成为独立的Line。
     * 但是如果其它的过程也会产生 X46/X47trans节点 就有可能引起解析混乱。
     */
    public static function transNode(int $cmd, int $env, AstNode $node1, $desc=''): AstNode {
        $node = new AstNode();
        $node->type2 = self::Trans;
        $node->nodes[] = $node1;

        $node->env = $env;
        $node->tcode = $cmd;
        $node->desc = $desc;
        return $node;
    }

    public static function transEvalEntryNode(int $cmd, AstNode $node1, $desc='') : AstNode {
        return static::transNode($cmd, self::TRANS_EVAL_ENTRY, $node1, $desc);
    }

    public static function transEvalMain1Node(int $cmd, AstNode $node1, $desc='') : AstNode {
        return static::transNode($cmd, self::TRANS_EVAL_MAIN1, $node1, $desc);
    }

    public static function transEvalSystemNode(int $cmd, AstNode $node1, $desc='') : AstNode {
        return static::transNode($cmd, self::TRANS_EVAL_SYSTEM, $node1, $desc);   
    }

    public static function literalNode(int $valType, $val, int $pcode=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Literal;
        // $node->valtype = $valType;
        $node->_valType = $valType;   //ItemValue::getValueTypeStr($valType);
        $node->val = $val;

        $node->pcode = $pcode;

        // moved from index into here，because there are alias in procedure calls. = =b
        // only literal can have alias?= =·
        $node->alias = '';

        return $node;
    }

    /**
     * in replacement of ItemValueArr
     * @param 'C|V' $constOrVar
     * @param 'G|L' $gOrl
     */
    public static function arrayNode($constOrVar, $gOrl, $name, $valType, $vals, $extra) : AstNode {
        // return new ItemValueArr($item_type, $item_name, $value_type, $array_dimen, $array_values);
        $node = new AstNode();
        $node->type2 = self::Array;

        $node->cov = $constOrVar;
        $node->_gol = $gOrl;

        $node->name = $name;
        $node->_valType = $valType;
        $node->_vals = $vals;
        $node->size = count($vals);
        $node->extra = $extra;

        return $node;
    }

    /**
     * in replacement of ItemValue
     * @param 'C|V' $constOrVar
     * @param 'G|L' $gOrl
     */
    public static function valueNode($constOrVar, $gOrl, $name, $valType, $val) : AstNode {
        // return new ItemValue($item_type, $item_name, $value_type, $item_value);
        $node = new AstNode();
        $node->type2 = self::Value;

        $node->cov = $constOrVar;
        $node->_gol = $gOrl;

        $node->name = $name;
        $node->_valType = $valType;
        $node->val = $val;

        return $node;
    }

    /** 
     * pre create procedure with the given info.
     * some info are supplied later.
     * $item_count = $param_count + $local_count + ($ret_count)
     */
    public static function procedureNode($porfore, $name, $offset, $param_count, $item_count) : AstRoot {
        $node = new AstRoot($offset, $porfore, $param_count, $item_count);
        $node->type2 = self::Procedure;

        $node->porfore = $porfore;
        $node->name = $name;

        // $node->setParamCounts($param_count, $item_count);
        // if (isset($ret_type)) {
        //     $node->setRetType($ret_type);
        // }

        return $node;
    }

    public static function unaryNode($oper, AstNode $node1, int $pcode=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Unary;
        $node->op = $oper;
        $node->nodes[] = $node1;
        
        $node->pcode = $pcode;

        return $node;
    }

    public static function binaryNode($oper, AstNode $node1, AstNode $node2, int $pcode=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Binary;
        $node->op = $oper;
        $node->nodes[] = $node1;
        $node->nodes[] = $node2;
        
        $node->pcode = $pcode;

        return $node;
    }

    // /**
    //  * @deprecated 
    //  */
    // public static function _arrayIdxrNode($id_index, ItemValueArr $item, AstNode $index_node) : AstNode {
    //     $node = new AstNode();
    //     $node->type2 = self::_Array;
    //     $node->name = $item->getName();
    //     // $node->scope = $id_index >=0 ? IRCore::SCOPE_GLOBAL : IRCore::SCOPE_LOCAL;
    //     $node->index = identIndex($id_index);
    //     // $node->vt = ItemValue::getValueTypeStr($item->getValueType());//$item->getValueTypeStr();
    //     $node->_item = $item;

    //     $node->nodes[] = $index_node;

    //     // ☆，mark $index_node as integer
    //     static::markIndexers($index_node);
       
    //     return $node;
    // }

    // /**
    //  * @deprecated version
    //  * change to AstRoot -> getValueNode();
    //  */
    // public static function _valueNode($id_index, ItemValue $item) : AstNode {
    //     $node = new AstNode();
    //     $node->type2 = self::_Value;
    //     $node->name = $item->getName();
    //     // $node->scope = $id_index >=0 ? IRCore::SCOPE_GLOBAL : IRCore::SCOPE_LOCAL;
    //     $node->index = identIndex($id_index);
    //     // $node->vt = ItemValue::getValueTypeStr($item->getValueType());//$item->getValueTypeStr();
    //     $node->_item = $item;

    //     return $node;
    // }

    // /**
    //  * @deprecated 
    //  * @param Astnode[] $params
    //  */
    // public static function _usercallNode($id_index, ItemProcedure $item, $params) : AstNode {
    //     $node = new AstNode();
    //     $node->type2 = self::_UserCall;
    //     $node->index = identIndex($id_index);
    //     $node->name = $item->getName();

    //     $node->nodes = $params;

    //     return $node;
    // }
    
    /** in replacement of _arrayIdxrNode, as an indexer */
    public static function arrayIdxrNode(AstNode $array, AstNode $index) : AstNode {
        return static::indexerNode($array, $index);
    }

    /**
     * @param Astnode[] $params
     */
    public static function usercallNode(AstRoot $root, $params) : AstNode {
        $node = new AstNode();
        $node->type2 = self::UserCall;
        // $node->index = identIndex($id_index);
        $node->name = $root->getName();
        $node->_object = $root;

        $node->nodes[] = static::blockOrEmptyNode($params);

        return $node;
    }

    public static function paramRefNode(AstNode $param) : AstNode {
        $node = new AstNode();
        $node->type2 = self::ParamRef;

        $node->nodes[] = $param;
        return $node;
    }

    public static function paramValNode(AstNode $param) : AstNode {
        $node = new AstNode();
        $node->type2 = self::ParamVal;

        $node->nodes[] = $param;
        return $node;
    }

    /**
     * @param Astnode[] $params
     */
    public static function syscallNode($callname, $params, int $pcode=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::SysCall;
        $node->name = $callname;

        $node->nodes[] = static::blockOrEmptyNode($params);

        $node->pcode = $pcode;

        return $node;
    }

    /**
     * @param Astnode[] $params
     */
    public static function callNode(Astnode $caller, $callname, $params, int $pcode=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Call;
        $node->name = $callname;

        // $node->_object = $caller;
        $node->nodes[] = $caller;

        $node->nodes[] = static::blockOrEmptyNode($params);

        $node->pcode = $pcode;

        return $node;
    }

    public static function objectNode($objname, int $pcode=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Object;
        $node->name = $objname;

        $node->pcode = $pcode;

        return $node;
    }

    public static function JoyStickNode() : AstNode {
        return static::objectNode('JoyStick', 0xB1);
    }

    public static function TrackPropertyNode() : AstNode {
        return static::objectNode('TrackProperty', 0x70);
    }

    public static function ModelCastNode() : AstNode {
        return static::objectNode('ModelCast', 0x80);
    }

    public static function WaveCastNode() : AstNode {
        return static::objectNode('WaveCast', 0x84);
    }

    public static function CameraCastNode() : AstNode {
        return static::objectNode('CameraCast', 0x87);
    }

    public static function LightCastNode() : AstNode {
        return static::objectNode('LightCast', 0x88);
    }

    public static function AVIFileNode() : AstNode {
        return static::objectNode('AVIFile', 0x59);
    }

    public static function subobjNode(AstNode $object, $subobjname, int $pcode=0, 
                                        int $valType=PCodeReader::VAL_UNKNOWN): AstNode {
        $node = new AstNode();
        $node->type2 = self::Subobject;

        // $node->_object = $object;
        $node->nodes[] = $object;
        $node->name = $subobjname;

        $node->pcode = $pcode;
        $node->_valType = $valType;
        return $node;
    }

    public static function indexerNode(Astnode $object, Astnode $index) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Indexer;

        // $node->_object = $object;
        $node->nodes[] = $object;
        $node->nodes[] = $index;
        static::markIndexerValType($index);
        
        return $node;
    }

    /**
     * for double index things like TrackProperty
     */
    public static function bindexerNode(Astnode $object, Astnode $index, AstNode $index2) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Bindexer;

        // $node->_object = $object;
        $node->nodes[] = $object;
        $node->nodes[] = $index;
        static::markIndexerValType($index);
        
        $node->nodes[] = $index2;
        static::markIndexerValType($index2);

        return $node;
    }

    public static function propNode(AstNode $object, $prop, $propname=null, $propValType=PCodeReader::VAL_UNKNOWN) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Proper;

        // $node->_object = $object;
        $node->nodes[] = $object;
        $node->prop = $prop;
        if (!empty($propname)) {
            $node->name = $propname;
        }
        $node->_valType = $propValType;

        return $node;
    }

    public static function assignNode(AstNode $node1, AstNode $node2, $is_string=false) : AstNode {
        $node = new AstNode();
        $node->type2 = self::Assign;

        $node->nodes[] = $node1;
        $node->nodes[] = $node2;

        if ($is_string)
            $node->is_string = $is_string;

        return $node;
    }

    public static function blockOrEmptyNode($nodes, $end=0) : AstNode {
        if (empty($nodes)) return static::emptyNode($end);
        return static::blockNode($nodes, $end);
    }

    public static function blockNode($nodes, $end=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::BLOCK;
        $node->nodes = $nodes;
        $node->end = $end;
        return $node;
    }

    public static function emptyNode($end=0) : AstNode {
        $node = new AstNode();
        $node->type2 = self::EMPTY;
        $node->end = $end;
        return $node;
    }

    public static function forCondNode(AstNode $itNode, AstNode $start, AstNode $end, AstNode $step) : AstNode {
        $node = new AstNode();
        $node->type2 = self::FORCOND;
        $node->nodes[] = $itNode;
        $node->nodes[] = $start;
        $node->nodes[] = $end;
        $node->nodes[] = $step;

        return $node;
    }

    public static function forNode(AstNode $cond, AstNode $block) : AstNode {
        $node = new AstNode();
        $node->type2 = self::FOR;
        $node->nodes[] = $cond;
        $node->nodes[] = $block;

        return $node;
    }

    public static function ifNode(AstNode $cond, AstNode $then, AstNode $else) : AstNode {
        $node = new AstNode();
        $node->type2 = self::IF;
        $node->nodes[] = $cond;
        $node->nodes[] = $then;
        $node->nodes[] = $else;

        return $node;
    }

    public static function caseCondNode($ranges) : AstNode {
        $range_arr = [];
        foreach ($ranges as $range) {
            if (is_array($range)) {
                $range_arr[] = $range[0] . '-' . $range[1];
            }else {
                $range_arr[] = $range;
            }
        }

        $node = new AstNode();
        $node->type2 = self::CASECOND;
        $node->desc = join(', ', $range_arr);
        $node->_ranges = $ranges;

        return $node;
    }

    public static function caseBranchNode(AstNode $cond, AstNode $block) : AstNode {
        $node = new AstNode();
        $node->type2 = self::CASEBRANCH;
        
        if ($cond->type2 == self::EMPTY) {
            $node->else = true;
        }
        $node->nodes[] = $cond;
        $node->nodes[] = $block;

        return $node;
    }

    public static function caseNode(AstNode $cond, $branches) : AstNode {
        $node = new AstNode();
        $node->type2 = self::CASE;

        $node->nodes = array_merge([$cond], $branches);

        return $node;
    }

    /** mark indexer node's val type，need to be 0x47 */
    private static function markIndexerValType(AstNode $indexr_node) {
        if ($indexr_node->type2 == self::Value) {
            // if ($indexr_node->vt == ItemValue::getValueTypeStr(ItemValue::VAL_UNKNOWN)) {
            //     $indexr_node->vt = ItemValue::getValueTypeStr(ItemValue::VAL_INT);
            // }
            // /** @var ItemValue */
            // $itemValue = $indexr_node->_item;
            // if ($itemValue->getValueType() == PCodeReader::VAL_UNKNOWN) {
            //     $itemValue->setValueType(PCodeReader::VAL_INT);
            // }
            if ($indexr_node->_valType == PCodeReader::VAL_UNKNOWN) {
                $indexr_node->_valType = PCodeReader::VAL_INT;
            }
        }

        if ($indexr_node->type2 == self::Trans) {
            if ($indexr_node->env == self::TRANS_EVAL_SYSTEM 
                    && $indexr_node->tcode == EvalSystem::X47) {
                static::markIndexerValType($indexr_node->nodes[0]);
            }
        }
    }

    /**
     * check node is Indexer and which index is Literal，send the node through callback.
     */
    public static function markIndexerAlias(AstNode $node, $callback) {
        if ($node->type2 == self::Indexer) {
            $literalNodes = [];
            $path = static::indexerToPath($node, $literalNodes);
            if (empty($path)) return;

            $alias = $callback($path);
            if (!empty($alias)) {
                $literalNodes[count($literalNodes)-1]->alias = $alias;
            }
        }
    }

    /**
     * check node is Bindexer and which index is Literal，send the node through callback.
     */
    public static function markBindexerAlias(AstNode $node, $callback) {
        if ($node->type2 == self::Bindexer) {
            $literalNodes = [];
            $path = static::indexerToPath($node, $literalNodes);
            if (empty($path)) return;

            [$alias, $alias2] = $callback($path);
            if (!empty($alias)) {
                $literalNodes[count($literalNodes)-2]->alias = $alias;
            }
            if (!empty($alias2)) {
                $literalNodes[count($literalNodes)-1]->alias = $alias2;
            }
        }
    }

    // SeekFrame dont support mixed labels，e.g. 0:label..
    public static function markSeekframeAlias(AstNode $node, $callback) {
        if ($node->type2 == self::SysCall) {
            if ($node->name == 'SeekFrameEx') {
                $block = $node->nodes[0];
                // $literalNodes = [];

                $literal = $block->nodes[0]->getLiteralValue();
                $literal2 = $block->nodes[1]->getLiteralValue();
                $thisPath = 'SeekFrameEx.' . 
                        ($literal===null?'?':$literal) . ':' . ($literal2===null?'?':$literal2);

                // $literalNodes[] = $block->nodes[0];
                // $literalNodes[] = $block->nodes[1];

                $path = [$thisPath];

                [$alias, $alias2] = $callback($path);
                if (!empty($alias)) {
                    // $literalNodes[count($literalNodes)-2]->alias = $alias;
                    $block->nodes[0]->alias = $alias;
                }
                if (!empty($alias2)) {
                    // $literalNodes[count($literalNodes)-1]->alias = $alias2;
                    $block->nodes[1]->alias = $alias2;
                }
            }
        }
    }

    public static function markGetCrossPointAlias(AstNode $node, $callback) {
        if ($node->type2 == self::SysCall) {
            if ($node->name == 'GetCrossPointEx' || $node->name == 'GetCrossPoint') {
                $block = $node->nodes[0];

                $literal = $block->nodes[7]->getLiteralValue();
                $literal2 = $block->nodes[8]->getLiteralValue();
                $thisPath = $node->name .'.' . 
                    ($literal===null?'?':$literal) . ':' . ($literal2===null?'?':$literal2);

                $path = [$thisPath];
                [$alias, $alias2] = $callback($path);
                if (!empty($alias)) {
                    $block->nodes[7]->alias = $alias;
                }
                if (!empty($alias2)) {
                    $block->nodes[8]->alias = $alias2;
                }
            }
        }
    }

    public static function markCollisionCheckAlias(AstNode $node, $callback) {
        if ($node->type2 == self::SysCall) {
            if ($node->name == 'CollisionCheck' || $node->name == 'CollisionCheckEx') {
                $block = $node->nodes[0];

                $literal = $block->nodes[0]->getLiteralValue();
                $literal2 = $block->nodes[1]->getLiteralValue();
                $thisPath = $node->name .'.' . 
                    ($literal===null?'?':$literal) . ':' . ($literal2===null?'?':$literal2);

                $path = [$thisPath];
                [$alias, $alias2] = $callback($path);
                if (!empty($alias)) {
                    $block->nodes[0]->alias = $alias;
                }
                if (!empty($alias2)) {
                    $block->nodes[1]->alias = $alias2;
                }

                $literal = $block->nodes[2]->getLiteralValue();
                $literal2 = $block->nodes[3]->getLiteralValue();
                $thisPath = $node->name .'.' . 
                    ($literal===null?'?':$literal) . ':' . ($literal2===null?'?':$literal2);

                $path = [$thisPath];
                [$alias, $alias2] = $callback($path);
                if (!empty($alias)) {
                    $block->nodes[2]->alias = $alias;
                }
                if (!empty($alias2)) {
                    $block->nodes[3]->alias = $alias2;
                }
            }
        }
    }

    public static function markBreakLoopExAlias(AstNode $node, $callback) {
        if ($node->type2 == self::SysCall) {
            if ($node->name == 'BreakLoopEx') {
                $block = $node->nodes[0];

                $literal = $block->nodes[0]->getLiteralValue();
                if ($literal === null) return;

                if ($literal === 0) {
                    $block->nodes[0]->alias = '0';
                    return ;
                }

                $thisPath = $node->name .'.' . $literal . ':?';

                $path = [$thisPath];
                [$alias, $alias2] = $callback($path);
                if (!empty($alias)) {
                    $block->nodes[0]->alias = $alias;
                }
            }
        }
    }

    public static function markAssignModelCastTexture(AstNode $node, $callback) {
        if ($node->type2 == self::Assign) {
            if ($node->nodes[0]->type2 == self::Proper &&
                $node->nodes[1]->type2 == self::Literal) {

                // check left node..
                $propNode = $node->nodes[0];
                $literalNode = $node->nodes[1];
                if ($propNode->nodes[0]->type2 == self::Indexer && $propNode->prop == 0x20) {//texture from ModelCast
                    $_literalNodes = [];
                    $path = static::indexerToPath($propNode->nodes[0], $_literalNodes);

                    if (empty($path)) return;
                    if (strpos($path[0], self::OBJ_ModelCast)===false) return;

                    $alias = $callback($literalNode->getLiteralValue());
                    if (!empty($alias)) {
                        $literalNode->alias = AstDecoder::castNumberWrap(CAST_TEXTURE, $alias);
                    }
                }
            }
        }
    }

    public static function markWaveAudioAlias(AstNode $node, $callback) {
        if ($node->type2 == self::SysCall) {
            if ($node->name == 'WaveAudio') {
                $block = $node->nodes[0];

                $literal = $block->nodes[0]->getLiteralValue();
                if ($literal === null) return;

                $alias = $callback($literal);
                if (!empty($alias)) {
                    $block->nodes[0]->alias = $alias;
                }
            }
        }
    }

    public static function markGetKeyEventAlias(AstNode $node, $callback) {
        if ($node->type2 == self::SysCall) {
            if ($node->name == 'GetKeyState') {
                $block = $node->nodes[0];

                $literal = $block->nodes[0]->getLiteralValue();
                if ($literal === null) return;

                $alias = $callback($literal);
                if (!empty($alias)) {
                    $block->nodes[0]->alias = $alias;
                }
            }
        }
    }

    /**
     * @param AstNode[] $literalNodes
     */
    private static function indexerToPath(AstNode $indexer, &$literalNodes) : array {
        $obj = $indexer->nodes[0]; // subobject/ array/ trackproperty?
        if ($obj->type2 == self::Subobject) {
            $parentPath = self::indexerToPath($obj->nodes[0], $literalNodes);
            // xx.x
            $literal = $indexer->nodes[1]->getLiteralValue();
            $thisPath = $obj->name . '.' . ($literal===null?'?':$literal);

            $parentPath[] = $thisPath;
            $literalNodes[] = $indexer->nodes[1];
        }
        else if ($obj->type2 == self::Object) {
            if ($obj->name == self::OBJ_TrackProperty) {
                // xxx.x:x
                $literal = $indexer->nodes[1]->getLiteralValue();
                $literal2 = $indexer->nodes[2]->getLiteralValue();
                $thisPath = $obj->name . '.' . 
                        ($literal===null?'?':$literal) . ':' . ($literal2===null?'?':$literal2);

                $literalNodes[] = $indexer->nodes[1];
                $literalNodes[] = $indexer->nodes[2];
            }
            else {
                // xxx.x
                $literal = $indexer->nodes[1]->getLiteralValue();
                $thisPath = $obj->name . '.' . ($literal===null?'?':$literal);

                $literalNodes[] = $indexer->nodes[1];
            }
            $parentPath = [$thisPath];
        } else {
            return [];
        }
        return $parentPath;
    }

    // /**
    //  * @deprecated 
    //  */
    // public static function xcallNode($cmd, $desc, 
    //         $var1=null, $var2=null, AstNode $node1=null, AstNode $node2=null) : AstNode {
    //     $node = new AstNode();
    //     $node->type2 = self::XCall;

    //     $node->desc = $desc;
    //     $node->pcode = $cmd;

    //     if (!empty($var1)) {
    //         $node->var1 = $var1;
    //     }
    //     if (!empty($var2)) {
    //         $node->var2 = $var2;
    //     }

    //     if (!empty($node1)) {
    //         $node->nodes[] = $node1;
    //     }
    //     if (!empty($node2)) {
    //         $node->nodes[] = $node2;
    //     }

    //     return $node;
    // }
    
}