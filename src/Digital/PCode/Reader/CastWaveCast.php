<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class CastWaveCast {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::WaveCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset); 
        // $node->prop = $prop;

        switch ($prop) {
            // ??
            case 0x10:
            case 0x12:
            case 0x23:
            case 0x24:
            case 0x25:
            case 0x26:
            case 0x27:
            case 0x29:
            case 0x2A:
                break;
        }

        $propname = ScriptWaveCast::names[$prop]??'';
        $propValType = ScriptWaveCast::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

        $obj_idxr_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);

        return $obj_idxr_prop;
    }

}