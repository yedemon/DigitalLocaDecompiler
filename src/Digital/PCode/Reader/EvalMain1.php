<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Exception;

/**
 * eval_main_1
 */
class EvalMain1 {
    static int $level = 0;

    /** IF -> eval_system */
    const IF = 0x10;

    /** FOR */
    const FOR = 0x11;

    /** FOR */
    const FOR_END = 0x12;

    /** FOR */
    const FOR_FIN = 0x13;

    /** ELSE */
    const ELSE = 0x14;

    /** END */
    const END = 0x15;
        
    /** HALT */
    const HALT = 0x16;

    /** CASE */
    const CASE = 0x17;

    /** CASE-COND */
    const CASE_COND = 0x18;

    /** CASE-END */
    const CASE_END = 0x19;

    /** INC */
    const INC = 0x20;

    /** DEC */
    const DEC = 0x21;
    
    /** FileReadOpen */
    const FileReadOpen = 0x30;

    /** FileWriteOpen */
    const FileWriteOpen = 0x31;

    /** FileReadClose */
    const FileReadClose = 0x32;

    /** FileWriteClose */
    const FileWriteClose = 0x33;

    /** FileRead */
    const FileRead = 0x34;
    
    /** FileWrite */
    const FileWrite = 0x35;

    /** OpenDialog */
    const OpenDialog = 0x40;

    /** SaveDialog */
    const SaveDialog = 0x41;

    /** MessageBox */
    const MessageBox = 0x42;

    /** caption =  */
    const CAPTION = 0x53;
    
    /** GetClientCursorPos */
    const GCCP = 0x55;
    
    /** GetScreenCursorPos */
    const GSCP = 0x56;

    /** SetClientCursorPos */
    const SCCP = 0x57;
    
    /** SetScreenCursorPos */
    const SSCP = 0x58;

    /** Randomize */
    const Randomize = 0x70;

    /** JoyStick */
    const JOYSTICK = 0xB1;

    /** Internal variables or methods  */
    const F0 = 0xF0;

    /** User variables or methods -> eval_system */
    const F2 = 0xF2;

    public static function digest(AstRoot $root, $bytes, $offset) {
        $nodes = [];

        $total = strlen($bytes);
        while($total > $offset) {
            $byte0 = r_u1($bytes, $offset); //upk0('C', substr($bytes, $offset, 1));  // don't offset
            if ($byte0 == self::END) {
                break;
            }

            $nodes[] = static::_digest($root, $bytes, $offset);
        }
        return $nodes;
    }

    // statement block.
    public static function _digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $cmd = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        if ($root->debug_print)
            print(tab(self::$level++) . dechex($cmd));

        switch($cmd) {
            case self::F0:
                $cnode = ScriptMain::digest($root, $bytes, $offset);
                $node = AstFactory::transEvalMain1Node(self::F0, $cnode);
                break;
            case self::F2:
                $cnode = static::digestXF2($root, $bytes, $offset);
                $node = AstFactory::transEvalMain1Node(self::F2, $cnode);
                break;
            case self::IF:
                $node = static::digestIF($root, $bytes, $offset);
                break;
            
            case self::FOR:
                $node = static::digestFOR($root, $bytes, $offset);
                break;

            case self::END:
                $node = AstFactory::syscallNode('Exit', [], self::END);
                break;

            case self::HALT:
                // $node->op = 'halt';
                $node = AstFactory::syscallNode('halt', [], self::HALT);
                break;

            case self::CASE:
                $node = static::digestCASE($root, $bytes, $offset);
                break;
            
            case self::INC:
                $node = static::digestInc($root, $bytes, $offset);
                break;
            
            case self::DEC:
                $node = static::digestDec($root, $bytes, $offset);
                break;

            case self::FileReadOpen:
                $cnode = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('FileReadOpen', [$cnode], $cmd);
                break;
            case self::FileWriteOpen:
                $cnode = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('FileWriteOpen', [$cnode], $cmd);
                break;
            case self::FileReadClose:
                $node = AstFactory::syscallNode('FileReadClose', [], $cmd);
                break;
            case self::FileWriteClose:
                $node = AstFactory::syscallNode('FileWriteClose', [], $cmd);
                break;

            case self::FileRead:
                $node = static::digestFileRead($root, $bytes, $offset);
                break;

            case self::FileWrite:
                $node = static::digestFileWrite($root, $bytes, $offset);
                break;

            case self::OpenDialog:
                $obj = AstFactory::objectNode('OpenDialog', self::OpenDialog);
                $node = EvalCommon::digest48B880(0, $obj, $root, $bytes, $offset);
                break;

            case self::SaveDialog:
                $obj = AstFactory::objectNode('OpenDialog', self::OpenDialog);
                $node = EvalCommon::digest48B880(0, $obj, $root, $bytes, $offset);
                break;

            case self::MessageBox:
                // $node->op = 'MessageBox';
                $params = [];
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('MessageBox', $params, self::MessageBox);
                break;

            case self::CAPTION:
                // $node->op = 'caption=';
                $node1 = AstFactory::objectNode('caption', self::CAPTION);
                $node2 = EvalSystem::digest($root, $bytes, $offset);

                $node = AstFactory::assignNode($node1, $node2);
                break;
            
            case self::GCCP:
                $node = static::digestGCCP($root, $bytes, $offset);
                break;

            case self::GSCP:
                $node = static::digestGSCP($root, $bytes, $offset);
                break;

            case self::SCCP:
                $node = static::digestSCCP($root, $bytes, $offset);
                break;

            case self::SSCP:
                $node = static::digestSSCP($root, $bytes, $offset);
                break;

            case self::Randomize:
                $node = AstFactory::syscallNode('Randomize', [], self::Randomize);
                break;

            case self::JOYSTICK:
                $node = ScriptJoyStick::digest($root, $bytes, $offset);
                break;

            default:
                throw new Exception('unknown cmd '. dechex($cmd) .' at '.$offset. ' name ' . $root->getName());
                // break;
        }

        self::$level--;
        if (self::$level == 0 && ($cmd == self::IF || $cmd == self::CASE)) {
            if ($root->debug_print)
                print(PHP_EOL);
        }

        return $node;
    }

    // equals sub_4914C8
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
                $node1 = AstFactory::arrayIdxrNode($vnode, $index_node); //AstFactory::_arrayIdxrNode($id_index, $itemValue, $index_node);
            } else {
                // $leaf = new AstLeaf();
                // $leaf->type = $scope;
                // $leaf->ident = $itemValue;
                // $leaf->index = identIndex($id_index);

                // $node->nodes[] = $leaf;
                $node1 = $vnode; //AstFactory::_valueNode($id_index, $itemValue);
            }

            // string?
            if ($vnode->isString()) {
                $byte0 = d_u1($bytes, $offset);
                if ($byte0 == 0x80) {
                    // ??
                    throw new Exception('string item 0x80 occured at '.$offset);
                }
            }

            // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
            $node2 = EvalSystem::digest($root, $bytes, $offset);
            // bytes irrelevant

            $node = AstFactory::assignNode($node1, $node2, $vnode->isString());
        }
        else {
            $node = EvalCommon::digest492CC0($vnode, $root, $bytes, $offset);
        }
        
        return $node;
    }

    private static function digestIF(AstRoot $root, $bytes, &$offset) : AstNode {
        $ifcond = EvalSystem::digest($root, $bytes, $offset);
        // 
        $if_not_offset = d_u4($bytes, $offset);

        $then = [];
        $hasElse = false;
        while ($if_not_offset > $offset + $root->getBaseOffset()) {
            $byte0 = r_u1($bytes, $offset);
            if ($byte0 == self::ELSE) {
                $hasElse = true;
                break;
            }

            $then[] = static::_digest($root, $bytes, $offset);
        }

        // $node->nodes[] = $ifnode;
        // $node->end_offset = $if_not_offset;
        $thenNode = AstFactory::blockOrEmptyNode($then, $if_not_offset);

        $elseNode = null;
        if ($hasElse) {
            if ($root->debug_print)
                print(tab(self::$level-1) . '_');

            $else = [];
            // has else.
            $byte0 = d_u1($bytes, $offset); //abandon.

            $else_offset = d_u4($bytes, $offset);

            while ($else_offset > $offset + $root->getBaseOffset()) {
                $else[] = static::_digest($root, $bytes, $offset);
            }

            // $node->nodes[] = $elsenode;
            // $node->end_offset = $else_offset;
            $elseNode = AstFactory::blockOrEmptyNode($else, $else_offset);
        } else {
            $elseNode = AstFactory::emptyNode(0);
        }

        $node = AstFactory::ifNode($ifcond, $thenNode, $elseNode);
        return $node;
    }

    private static function digestCASE(AstRoot $root, $bytes, &$offset) : AstNode {
        // $node->op = 'case';
        $node1 = EvalSystem::digest($root, $bytes, $offset);

        $branches = [];

        $total = strlen($bytes);
        while($total > $offset) {
            $byte0 = r_u1($bytes, $offset); //upk0('C', substr($bytes, $offset, 1));  // don't offset
            if ($byte0 == self::CASE_END) {
                break;
            }

            if ($byte0 != self::CASE_COND) {
                // else for case...
                if ($root->debug_print)
                    print(tab(self::$level-1) . '_');
                $branches[] = static::digestCASEELSE($root, $bytes, $offset);
            }
            else {
                if ($root->debug_print)
                    print(tab(self::$level-1) . ':');
                $branches[] = static::digestCASECOND($root, $bytes, $offset);
            }
        }

        $byte0 = d_u1($bytes, $offset); // consume 0x19;
        // check every node end?
        $node = AstFactory::caseNode($node1, $branches);
        return $node;
    }

    private static function digestCASECOND(AstRoot $root, $bytes, &$offset) : AstNode {
        // $casenode = new EvalCommon();
        // $casenode->tag = "cond";

        $byte0 = d_u1($bytes, $offset);// useless
        $in_cond_offset = d_u4($bytes, $offset);

        // read conditions.
        $conds = [];
        while(true) {
            $byte0 = d_u1($bytes, $offset);
            if ($byte0 == 0xFF) {
                break;
            }
            if ($byte0 == 0) {
                $conds[] = d_4($bytes, $offset);
            }
            else if ($byte0 == 1) {
                $conds[] = [d_4($bytes, $offset), d_4($bytes, $offset)];
            }
        }
        $condNode = AstFactory::caseCondNode($conds);
        // $casenode->cond = $conds;
        $end_cond_offset = d_u4($bytes, $offset);

        if ($in_cond_offset != $offset + $root->getBaseOffset()) {
            throw new Exception('in cond offset not match at '.$offset);
        }

        $block = [];
        while (true) {
            $byte0 = r_u1($bytes, $offset);
            if ($byte0 == self::ELSE) {
                break;
            }

            $block[] = static::_digest($root, $bytes, $offset);
        }

        $byte0 = d_u1($bytes, $offset); // useless
        $end_case_offset = d_u4($bytes, $offset);

        if ($end_cond_offset != $offset + $root->getBaseOffset()) {
            throw new Exception('end cond offset not match at '.$offset);
        }

        // $casenode->end_offset = $end_case_offset;
        $blockNode = AstFactory::blockOrEmptyNode($block, $end_case_offset);
        $node = AstFactory::caseBranchNode($condNode, $blockNode);
        return $node;
    }

    private static function digestCASEELSE(AstRoot $root, $bytes, &$offset) : AstNode {
        $block = [];
        while (true) {
            $byte0 = r_u1($bytes, $offset);
            if ($byte0 == self::ELSE) {
                break;
            }

            $block[] = static::_digest($root, $bytes, $offset);
        }

        $byte0 = d_u1($bytes, $offset); // useless
        $end_case_offset = d_u4($bytes, $offset);

        // $casenode->end_offset = $end_case_offset;
        $blockNode = AstFactory::blockOrEmptyNode($block, $end_case_offset);

        $node = AstFactory::caseBranchNode(AstFactory::emptyNode(), $blockNode);
        return $node;
    }

    private static function digestFOR(AstRoot $root, $bytes, &$offset) : AstNode {
        $it = EvalCommon::digest489CF8($root, $bytes, $offset);
        $start = EvalSystem::digest($root, $bytes, $offset);
        $end = EvalSystem::digest($root, $bytes, $offset);
        $step = EvalSystem::digest($root, $bytes, $offset);
        $forCond = AstFactory::forCondNode($it, $start, $end, $step);

        $for_end_offset = d_u4($bytes, $offset);

        $_for_start_offset = $offset;

        $block = [];
        while ($for_end_offset > $offset + $root->getBaseOffset()) {
            $byte0 = r_u1($bytes, $offset);
            if ($byte0 == self::FOR_END) {
                break;
            }

            $block[] = static::_digest($root, $bytes, $offset);
        }

        $blockNode = AstFactory::blockOrEmptyNode($block, $for_end_offset);

        $byte0 = d_u1($bytes, $offset);
        $for_start_offset = d_u4($bytes, $offset);

        if ($for_start_offset != $_for_start_offset + $root->getBaseOffset()) {
            throw new Exception('for start offset not match '.$offset);
        }

        if ($for_end_offset != $offset + $root->getBaseOffset()) {
            throw new Exception('for end offset not match '.$offset);
        }

        $byte0 = d_u1($bytes, $offset);
        if ($byte0 != self::FOR_FIN) {
            throw new Exception('for fin offset not match '.$offset);
        }

        $node = AstFactory::forNode($forCond, $blockNode);
        return $node;
    }

    private static function digestInc(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);

        $node = AstFactory::syscallNode('inc', $params, self::INC);
        return $node;
    }

    private static function digestDec(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);

        $node = AstFactory::syscallNode('dec', $params, self::DEC);
        return $node;
    }

    private static function digestGCCP(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);  //x
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);  //y
        $node = AstFactory::syscallNode('GetClientCursorPos', $params, self::GCCP);

        return $node;
    }

    private static function digestGSCP(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);  //x
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);  //y
        $node = AstFactory::syscallNode('GetScreenCursorPos', $params, self::GSCP);

        return $node;
    }

    private static function digestSCCP(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        $params[] = EvalSystem::digest($root, $bytes, $offset);  //x
        $params[] = EvalSystem::digest($root, $bytes, $offset);  //y
        $node = AstFactory::syscallNode('SetClientCursorPos', $params, self::SCCP);

        return $node;
    }

    private static function digestSSCP(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);  //x
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);  //y
        $node = AstFactory::syscallNode('SetScreenCursorPos', $params, self::SSCP);

        return $node;
    }

    private static function digestFileRead(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];

        $id_index = d_4($bytes, $offset);
        // $itemValue = $root->getItemValue($id_index);
        $vnode = $root->getValueNode($id_index);

        // $scope = $id_index >=0 ? AstUnit::GLOBAL : AstUnit::LOCAL;
        if ($vnode->isArray()) {
            // $arr = new AstNode();
            // $arr->type = $scope;
            // $arr->ident = $itemValue;
            $arr_index = EvalSystem::digest($root, $bytes, $offset);

            // $node->nodes[] = $arr;
            $params[] = AstFactory::arrayIdxrNode($vnode, $arr_index); //AstFactory::_arrayIdxrNode($id_index, $itemValue, $arr_index);
        } else {
            // $leaf = new AstLeaf();
            // $leaf->type = $scope;
            // $leaf->ident = $itemValue;
            // $leaf->index = identIndex($id_index);

            // $node->nodes[] = $leaf;
            $params[] = $vnode; //AstFactory::_valueNode($id_index, $itemValue);
        }

        $node = AstFactory::syscallNode('FileRead', $params, self::FileRead);
        return $node;
    }

    private static function digestFileWrite(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];

        $id_index = d_4($bytes, $offset);
        // $itemValue = $root->getItemValue($id_index);
        $vnode = $root->getValueNode($id_index);

        // $scope = $id_index >=0 ? AstUnit::GLOBAL : AstUnit::LOCAL;
        if ($vnode->isArray()) {
            // $arr = new AstNode();
            // $arr->type = $scope;
            // $arr->ident = $itemValue;
            $arr_index = EvalSystem::digest($root, $bytes, $offset);

            // $node->nodes[] = $arr;
            $params[] = AstFactory::arrayIdxrNode($vnode, $arr_index); //AstFactory::_arrayIdxrNode($id_index, $itemValue, $arr_index);
        } else {
            // $leaf = new AstLeaf();
            // $leaf->type = $scope;
            // $leaf->ident = $itemValue;
            // $leaf->index = identIndex($id_index);

            // $node->nodes[] = $leaf;
            $params[] = $vnode; //AstFactory::_valueNode($id_index, $itemValue);
        }

        $node = AstFactory::syscallNode('FileWrite', $params, self::FileWrite);
        return $node;
    }
}
