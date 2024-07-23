<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

/** 
 * after 0x46
 */
class EvalEntry {

    /** FileReadOpen */
    const FileReadOpen = 0x30;

    /** FileWriteOpen */
    const FileWriteOpen = 0x31;

    /** FileReadClose */
    const FileReadClose = 0x32;

    /** FileWriteClose */
    const FileWriteClose = 0x33;

    /** OpenDialog */
    const OpenDialog = 0x40;

    /** SaveDialog */
    const SaveDialog = 0x41;

    /** Random */
    const RANDOM = 0x71;

    /** GetKeyState */
    const GKS = 0xB0;

    /** JoyStick */
    const JoyStick = 0xB1;

    /** CAST, 执行Script CAST */
    const CAST = 0xF1;

    const XF2 = 0xF2;

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $cmd = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        
        switch($cmd) {
            case self::FileReadOpen:
                // $node->op = 'FileReadClose';
                // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
                $cnode = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('FileReadOpen', [$cnode], $cmd);
                break;
            case self::FileWriteOpen:
                // $node->op = 'FileWriteClose';
                // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
                $cnode = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('FileWriteOpen', [$cnode], $cmd);
                break;
            case self::FileReadClose:
                // $node->op = 'FileReadClose';
                $node = AstFactory::syscallNode('FileReadClose', [], $cmd);
                break;
            case self::FileWriteClose:
                // $node->op = 'FileWriteClose';
                $node = AstFactory::syscallNode('FileWriteClose', [], $cmd);
                break;

            case self::OpenDialog:
                $obj = AstFactory::objectNode('OpenDialog', self::OpenDialog);
                $node = EvalCommon::digest48B880(1, $obj, $root, $bytes, $offset);
                break;
            case self::SaveDialog:
                $obj = AstFactory::objectNode('SaveDialog', self::SaveDialog);
                $node = EvalCommon::digest48B880(1, $obj, $root, $bytes, $offset);
                break;

            case self::RANDOM:
                // $node->op = 'Random';
                // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
                $cnode = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('Random', [$cnode], $cmd);
                break;
            
            case self::GKS:
                // $node->op = 'GetKeyState';
                // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
                $cnode = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('GetKeyState', [$cnode], $cmd);
                break;

            case self::JoyStick:
                $node = static::digestJoyStick($root, $bytes, $offset);
                break;

            case self::CAST:
                // $node->op = 'CAST';
                // $node->nodes[] = ScriptCast::digest($root, $bytes, $offset); 
                $cnode = ScriptCast::digest($root, $bytes, $offset);
                $node = AstFactory::transEvalEntryNode($cmd, $cnode, 'CAST');
                break;

            case self::XF2:
                $cnode = static::digestXF2($root, $bytes, $offset);
                $node = AstFactory::transEvalEntryNode(self::XF2, $cnode);
                break;

        }

        return $node;
    }

    /**
     * This method seems to only retrieve variables, so there is no final assignment operation.
     */
    private static function digestXF2(AstRoot $root, $bytes, &$offset) : AstNode {
        //sub_489C54
        $id_index = d_var(VT_INT, $bytes, $offset);
        // $itemValue = $root->getItemValue($id_index);
        $vnode = $root->getValueNode($id_index);
        //sub_489C54 ends

        if (!($vnode instanceof AstRoot)) {
            // $scope = $id_index >=0 ? AstUnit::GLOBAL : AstUnit::LOCAL;
            if ($vnode->isArray()) {
                // $arr = new AstNode();
                // $arr->type = $scope;
                // $arr->ident = $itemValue;
                $index_node = EvalSystem::digest($root, $bytes, $offset);

                // $node->nodes[] = $arr;
                $node = AstFactory::arrayIdxrNode($vnode, $index_node); //AstFactory::_arrayIdxrNode($id_index, $itemValue, $index_node);
            } else {
                // $leaf = new AstLeaf();
                // $leaf->type = $scope;
                // $leaf->ident = $itemValue;
                // $leaf->index = identIndex($id_index);
                
                // $node->nodes[] = $leaf;
                $node = $vnode; //AstFactory::_valueNode($id_index, $itemValue);
            }
            // bytes irrelevant
            
        } else {
            $node = EvalCommon::digest492CC0($vnode, $root, $bytes, $offset);
        }

        return $node;
    }

    private static function digestJoyStick(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::JoyStickNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        // If this index is a Literal, we can try to label it.
        $obj_index_alias = ScriptJoyStick::getJoyStickIndexAlias($obj_index);

        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);
        if (!empty($obj_index_alias)) {
            $obj_idxr->alias = $obj_index_alias;
        }

        $prop = d_u1($bytes, $offset);

        $propname = ScriptJoyStick::names[$prop]??''; 
        $propValType = ScriptJoyStick::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

        switch($prop) {
            case 0x11:
                $node = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                break;
            case 0x12:
            case 0x13:
            case 0x14:
            case 0x15:
            case 0x16:
            case 0x17:
            case 0x18:
                $node = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                break;
            case 0x19:
                // .ButtonStat
                $obj_bs_index = EvalSystem::digest($root, $bytes, $offset);
                // 如果这个index是个Literal，就可以尝试标签化
                $obj_bs_index_alias = ScriptJoyStick::getJoyStickButtonAlias($obj_bs_index);
                if (!empty($obj_bs_index_alias)) {
                    $obj_bs_index->alias = $obj_bs_index_alias;
                }
                
                $obj_idxr_bs = AstFactory::subobjNode($obj_idxr, 'ButtonStat', 0x19, PCodeReader::VAL_BOOL);
                $node = AstFactory::indexerNode($obj_idxr_bs, $obj_bs_index);
                break;
        }

        return $node;
    }
}
