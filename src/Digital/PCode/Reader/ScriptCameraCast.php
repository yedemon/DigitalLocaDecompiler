<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

class ScriptCameraCast {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::CameraCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);
        // $node->prop = $prop;

        if ($prop != 0x11) {
            $node2 = EvalSystem::digest($root, $bytes, $offset);

            $obj_prop = AstFactory::propNode($obj_idxr, $prop, '');
            $node = AstFactory::assignNode($obj_prop, $node2);
        } else {
            //Reset
            $node = AstFactory::callNode($obj_idxr, 'Reset', [], 0x11);
        }

        switch ( $prop )
        {
            case 0x10:
            case 0x11:
            case 0x20:
            case 0x21:
            case 0x22:
            case 0x23:
            case 0x24:
            case 0x25:
            case 0x26:
            case 0x27:
            case 0x28:
            case 0x29:
            case 0x2A:
                break;
        }

        return $node;
    }

}