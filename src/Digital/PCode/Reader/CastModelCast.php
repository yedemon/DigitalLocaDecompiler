<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

class CastModelCast {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::ModelCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);
        // 
        $prop = d_u1($bytes, $offset);
        // $node->prop = $prop;

        if ($prop == 0x80) {
            // group access.
            $obj_group_index = EvalSystem::digest($root, $bytes, $offset);

            $obj_idxr_group = AstFactory::subobjNode($obj_idxr, 'Group', 0x80);
            $obj_idxr_group_idxr = AstFactory::indexerNode($obj_idxr_group, $obj_group_index);
            
            $prop0 = d_u1($bytes, $offset);

            $prop0name = ScriptModelCast::names[$prop0]??''; 
            $prop0ValType = ScriptModelCast::valTypes[$prop0]??'';

            $obj_group_prop = AstFactory::propNode($obj_idxr_group_idxr, $prop0, $prop0name, $prop0ValType);
            $node = $obj_group_prop;

            // if ($byte0 != 0x11) {
            //     $cnode = new AstNode();
            //     $cnode->op = cmdhex($byte0);
            //     $node->nodes[] = $cnode;
            // } else {
            // }
        } else {
            $propname = ScriptModelCast::names[$prop]??''; 
            $propValType = ScriptModelCast::valTypes[$prop]??'';

            $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
            $node = $obj_prop;
        }

        return $node;
    }

}