<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;
use Exception;

/**
 * should end with 0xFF?
 */
class EvalSystem {
    /** +(V) */
    const ADD_VAR = 0x10;

    /** - */
    const NEG = 0x11;

    /** - */
    const SUB_VAR = 0x12;

    /** not */
    const NOT = 0x13;

    /** result valType is same with the first one? */
    const MUL_VAR = 0x14;

    /** / */
    const DIV_FLOAT = 0x15;

    /** and */
    const AND_INT = 0x18;

    /** or */
    const OR_INT = 0x19;

    /** = */
    const EQ = 0x1D;

    /** <> */
    const NEQ = 0x1E;

    /** < */
    const LT = 0x1F;

    /** > */
    const GT = 0x20;

    /** <= */
    const LTE = 0x21;

    /** >= */
    const GTE = 0x22;

    /** X24, ROL */
    const X24 = 0x24;

    /** X25, ROR */
    const X25 = 0x25;

    /** + */
    const ADD_INT = 0x26;

    /** + */
    const ADD_FLOAT = 0x27;

    const ADD_STRING = 0x28;

    /** - */
    const SUB_INT = 0x29;

    /** - */
    const SUB_FLOAT = 0x2A;

    /** * */
    const MUL_INT = 0x2B;

    /** * */
    const MUL_FLOAT = 0x2C;

    /** an integer literal */
    const X40 = 0x40;

    /** an double literal */
    const X41 = 0x41;

    /** an string literal */
    const X42 = 0x42;

    /** True */
    const X43 = 0x43;

    /** False */
    const X44 = 0x44;

    /** get inner vars/methods, goes into eval_entry */
    const X46 = 0x46;

    /** get user vars/methods */
    const X47 = 0x47;

    /** FloatToStr */
    const FloatToStr = 0x62;

    /** StrLength */
    const StrLength = 0x68;

    /** StrCopyRight */
    const StrCopyRight = 0x6D;

    /** StrDeleteRight */
    const StrDeleteRight = 0x6F;

    /** Trunc */
    const TRUNC = 0x71;

    /** abs */
    const ABS = 0x80;

    /** abs */
    const ABSF = 0x81;

    /** arctan2 */
    const ARCTAN2 = 0x87;

    /** cos */
    const COS = 0x8A;

    /** SIN */
    const SIN = 0x9E;

    /** SQRT */
    const SQRT = 0xA1;

    /** end */
    const END = 0xFF;

    public static function oper2Op($oper) {
        $op = '';
        switch( $oper ){
            case self::ADD_VAR:
                $op = '+(V)';
                break;
            case self::NEG:
                $op = 'neg';
                break;
            case self::SUB_VAR:
                $op = '-(V)';
                break;
            case self::NOT:
                $op = 'not';
                break;
            case self::MUL_VAR:
                $op = '*(V)';
                break;
            case self::DIV_FLOAT:
                $op = '/(f)';
                break;
            case self::AND_INT:
                $op = 'and';
                break;
            case self::OR_INT:
                $op = 'or';
                break;
            case self::NEQ:
                $op = '<>';
                break;
            case self::EQ:
                $op = '=';
                break;
            case self::GT:
                $op = '>';
                break;
            case self::LT:
                $op = '<';
                break;
            case self::GTE:
                $op = '>=';
                break;
            case self::LTE:
                $op = '<=';
                break;
            case self::SUB_INT:
                $op = '-';
                break;
            case self::SUB_FLOAT:
                $op = '-(f)';
                break;
            case self::ABS:
                $op = 'abs';
                break;
            case self::ABSF:
                $op = 'absF';
                break;
            case self::SIN:
                $op = 'sin';
                break;
            case self::COS:
                $op = 'cos';
                break;
            case self::SQRT:
                $op = 'sqrt';
                break;
            case self::ARCTAN2:
                $op = 'arctan2';
                break;
            case self::MUL_INT:
                $op = '*';
                break;
            case self::MUL_FLOAT:
                $op = '*(f)';
                break;
            case self::ADD_INT:
                $op = '+';
                break;
            case self::ADD_FLOAT:
                $op = '+(f)';
                break;
            case self::TRUNC:
                $op = 'Trunc';
                break;

            case self::FloatToStr:
                $op = 'FloatToStr';
                break;
            case self::StrLength:
                $op = 'StrLength';
                break;
            case self::StrCopyRight:
                $op = 'StrCopyRight';
                break;
            case self::StrDeleteRight:
                $op = 'StrDeleteRight';
                break;
            case self::X24:
                $op = 'X24';
                break;
            case self::X25:
                $op = 'X25';
                break;
            case self::ADD_STRING:
                $op = 'StrAdd';
        }

        return $op;
    }

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $node_queue = [];

        $loop = true;
        while( $loop ){
            $oper = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));

            $op = static::oper2Op($oper);

            switch ($oper) {
                case self::NEG:
                case self::NOT:
                case self::ABS:
                case self::ABSF: 
                case self::COS:
                case self::SIN:
                case self::SQRT:
                case self::TRUNC:

                case self::FloatToStr:
                case self::StrLength:
                    if (count($node_queue) == 0 ) {
                        throw new Exception($op.' no operants at '. $offset);
                    }

                    // $opnode = new AstNode();
                    // $opnode->type = AstUnit::OPERANT;
                    // $opnode->op = $op;
                    // $opnode->nodes[] = array_pop($node->nodes);
                    // $node->nodes[] = $opnode;

                    $_node = array_pop($node_queue);

                    $node_queue[] = AstFactory::unaryNode($op, $_node, $oper);
                    break;

                case self::ADD_VAR:
                case self::SUB_VAR:
                case self::DIV_FLOAT:
                case self::AND_INT:
                case self::OR_INT:
                case self::NEQ:
                case self::EQ:
                case self::LT:
                case self::GT:
                case self::LTE:
                case self::GTE:
                case self::SUB_INT:
                case self::SUB_FLOAT:
                case self::ARCTAN2:
                case self::MUL_VAR:
                case self::MUL_INT:
                case self::MUL_FLOAT:
                case self::ADD_INT:
                case self::ADD_FLOAT:

                case self::StrCopyRight:
                case self::StrDeleteRight:

                case self::X24:
                case self::X25:
                case self::ADD_STRING:
                    if (count($node_queue) <2 ) {
                        throw new Exception($op.' less than 2 operants at '. $offset);
                    }
                    // $opnode = new AstNode();
                    // $opnode->type = AstUnit::OPERANT;
                    // $opnode->op = $op;
                    
                    // $_ = array_pop($node->nodes);
                    // $__ = array_pop($node->nodes);
                    // $opnode->nodes[] = $__;
                    // $opnode->nodes[] = $_;

                    // $node->nodes[] = $opnode;

                    $_node2 = array_pop($node_queue);
                    $_node1 = array_pop($node_queue);

                    $node_queue[] = AstFactory::binaryNode($op, $_node1, $_node2, $oper);
                    break;

                case self::X40:
                    // $leaf = new AstLeaf();
                    // $leaf->type = AstUnit::VALUE;
                    // $leaf->val_type = IRCore::VAL_INT;
                    // $leaf->value = d_var(IRCore::VAL_INT, $bytes, $offset);
                    // $node->nodes[] = $leaf;
                    $value = d_var(VT_INT, $bytes, $offset);
                    $node_queue[] = AstFactory::literalNode(PCodeReader::VAL_INT, $value, self::X40);
                    break;
                case self::X41:
                    // $leaf = new AstLeaf();
                    // $leaf->type = AstUnit::VALUE;
                    // $leaf->val_type = IRCore::VAL_FLOAT;
                    // $leaf->value = d_var(IRCore::VAL_FLOAT, $bytes, $offset);
                    // $node->nodes[] = $leaf;
                    $value = d_var(VT_FLOAT, $bytes, $offset);
                    $node_queue[] = AstFactory::literalNode(PCodeReader::VAL_FLOAT, $value, self::X41);
                    break;
                case self::X42:
                    // $leaf = new AstLeaf();
                    // $leaf->type = AstUnit::VALUE;
                    // $leaf->val_type = IRCore::VAL_STRING;
                    // $leaf->value = d_var(IRCore::VAL_STRING, $bytes, $offset);
                    // $node->nodes[] = $leaf;
                    $value = d_var(VT_STRING, $bytes, $offset);
                    $node_queue[] = AstFactory::literalNode(PCodeReader::VAL_STRING, $value, self::X42);
                    break;

                case self::X43;
                    // $leaf = new AstLeaf();
                    // $leaf->type = AstUnit::VALUE;
                    // $leaf->val_type = IRCore::VAL_BOOL;
                    // $leaf->value = TRUE;
                    // $node->nodes[] = $leaf;
                    $node_queue[] = AstFactory::literalNode(PCodeReader::VAL_BOOL, TRUE, self::X43);
                    break;
                case self::X44;
                    // $leaf = new AstLeaf();
                    // $leaf->type = AstUnit::VALUE;
                    // $leaf->val_type = IRCore::VAL_BOOL;
                    // $leaf->value = FALSE;
                    // $node->nodes[] = $leaf;
                    $node_queue[] = AstFactory::literalNode(PCodeReader::VAL_BOOL, FALSE, self::X44);
                    break;

                case self::X46:
                    $node_queue[] = AstFactory::transEvalSystemNode(self::X46,
                        EvalEntry::digest($root, $bytes, $offset));
                    break;

                case self::X47:
                    // EvalCommon :: digestLocal?
                    $node_queue[] = static::digestX47($root, $bytes, $offset);
                    break;

                case self::END:
                    $loop = false;
                    break;
            }
        }

        // remove evalsystem?
        if (count($node_queue) == 1) {
            return $node_queue[0];
        } else {
            throw new Exception('eval system should only return one node. '.$offset);
        }
    }

    private static function digestX47(AstRoot $root, $bytes, &$offset) : AstNode {
        $id_index = d_var(VT_INT, $bytes, $offset);
        // $itemValue = $root->getItemValue($id_index);
        $vnode = $root->getValueNode($id_index);
        
        if ($vnode->isArray()) {
            // $arr = new AstNode();
            // $arr->type = $scope;
            // $arr->ident = $itemValue;
            // $arr->nodes[] = EvalSystem::digest($root, $bytes, $offset);

            // $node->nodes[] = $arr;
            $index_node = EvalSystem::digest($root, $bytes, $offset);

            $node = AstFactory::transEvalSystemNode(self::X47,
                    AstFactory::arrayIdxrNode($vnode, $index_node) /*AstFactory::_arrayIdxrNode($id_index, $itemValue, $index_node)*/);
        } else {
            // $leaf = new AstLeaf();
            // $leaf->type = $scope;
            // $leaf->ident = $itemValue;
            // $leaf->index = identIndex($id_index);

            // $node->nodes[] = $leaf;
            $node = AstFactory::transEvalSystemNode(self::X47,
                    $vnode/*AstFactory::_valueNode($id_index, $itemValue)*/);
        }
        return $node;
    }
}
