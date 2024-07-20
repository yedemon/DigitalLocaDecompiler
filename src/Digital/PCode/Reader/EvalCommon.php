<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Exception;

/**
 */
class EvalCommon {
    /**
     */
    public static function digest492CC0(AstRoot $procedure, AstRoot $root, $bytes, &$offset) : AstNode {
        // $node = new AstNode();
        // $node->type = AstUnit::PROCEDURE;
        // $node->val = $procedure->getName();

        // $paramCount = $procedure->getParamCount();
        $paramCount = $procedure->getParamCount();

        $params = [];
        for ($i = 0; $i < $paramCount; ++$i) {
            $byte0 = d_u1($bytes, $offset);// useless

            switch ($byte0) {
                case 0x10:
                    // var param... ref...
                    $_id_index = d_var(VT_INT, $bytes, $offset);
                    // $itemValue = $root->getItemValue($_id_index);
                    $_vnode = $root->getValueNode($_id_index);

                    // $cnode->type = $scope;
                    // $cnode->ident = $itemValue;
                    // $cnode->index = identIndex($_id_index);
                    $byte0 = d_u1($bytes, $offset);
                    if ($byte0 == 0x80) {
                        $arr_index = EvalSystem::digest($root, $bytes, $offset);
                        // $param = AstFactory::_arrayIdxrNode($_id_index, $itemValue, $arr_index);
                        $params = AstFactory::arrayIdxrNode($_vnode, $arr_index);
                    } else {
                        // $param = AstFactory::_valueNode($id_index, $itemValue);
                        $param = $_vnode;
                    }

                    $params[] = AstFactory::paramRefNode($param);
                    break;

                case 0x20:
                    // $cnode = new AstNode();
                    // $cnode->op = '0x20';
                    // $cnode->nodes[] = EvalSystem::digest($root, $bytes, $offset);
                    $param = EvalSystem::digest($root, $bytes, $offset);

                    $params[] = AstFactory::paramValNode($param);
                    break;

                default:
                    throw new Exception("unknown procedure param type at ".$offset);
                    // break;
            }
        }

        // $node->nodes = $params;
        // $node = AstFactory::_usercallNode($id_index, $procedure, $params);
        $node = AstFactory::usercallNode($procedure, $params);
        return $node;
    }

    /**
     */
    public static function digest489CF8(AstRoot $root, $bytes, &$offset) : AstNode {
        $id_index = d_var(VT_INT, $bytes, $offset);
        // $itemValue = $root->getItemValue($id_index);
        $vnode = $root->getValueNode($id_index);

        // $scope = $id_index >=0 ? AstUnit::GLOBAL : AstUnit::LOCAL;
        if ($vnode->isArray()) {
            // $arr = new AstNode();
            // $arr->type = $scope;
            // $arr->ident = $itemValue;
            // $arr->nodes[] = EvalSystem::digest($root, $bytes, $offset);

            // return $arr;
            $index_node = EvalSystem::digest($root, $bytes, $offset);
            $node = AstFactory::arrayIdxrNode($vnode, $index_node); //AstFactory::_arrayIdxrNode($id_index, $itemValue, $index_node);
        } else {
            // $leaf = new AstLeaf();
            // $leaf->type = $id_index >=0 ? AstLeaf::GLOBAL : AstLeaf::LOCAL;
            // $leaf->ident = $itemValue;
            // $leaf->index = identIndex($id_index);

            // return $leaf;
            $node = $vnode; //AstFactory::_valueNode($id_index, $itemValue);
        }

        return $node;
    }

    /**
     * OpenDialog / SaveDialog.
     * extra - evalMain = 0, evalEntry = 1;
     */
    public static function digest48B880($extra, $obj, AstRoot $root, $bytes, &$offset) : AstNode {
        $type = d_u1($bytes, $offset);
        // $node->type = $type;

        switch ($type) {
            case 0x00:
                // Bool..Execute
                //??
                $node = AstFactory::callNode($obj, 'Execute', [], 0x00);
                break;
            case 0x01:
                if ($extra == 0) {
                    $node2 = EvalSystem::digest($root, $bytes, $offset);

                    $obj_prop = AstFactory::propNode($obj, 0x01, '');
                    $node = AstFactory::assignNode($obj_prop, $node2);
                } else {
                    $node = AstFactory::propNode($obj, 0x01, '');
                }
                break;
            case 0x02:
                if ($extra == 0) {
                    $node2 = EvalSystem::digest($root, $bytes, $offset);

                    $obj_prop = AstFactory::propNode($obj, 0x02, '');
                    $node = AstFactory::assignNode($obj_prop, $node2);
                } else {
                    $node = AstFactory::propNode($obj, 0x02, '');
                }
                break;
            case 0x03:
                if ($extra == 0) {
                    $node2 = EvalSystem::digest($root, $bytes, $offset);

                    $obj_prop = AstFactory::propNode($obj, 0x03, '');
                    $node = AstFactory::assignNode($obj_prop, $node2);
                } else {
                    $node = AstFactory::propNode($obj, 0x03, '');
                }
                break;
            case 0x04:
                if ($extra == 0) {
                    $node2 = EvalSystem::digest($root, $bytes, $offset);

                    $obj_prop = AstFactory::propNode($obj, 0x04, '');
                    $node = AstFactory::assignNode($obj_prop, $node2);
                } else {
                    $node = AstFactory::propNode($obj, 0x04, '');
                }
                break;
        }

        // return AstFactory::xcallNode($cmd, 'call 48B880 with extra='.$extra, $extra, null, $cnode);
        return $node;
    }

}