<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class CastTrackProp {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::TrackPropertyNode();

        // $obj_index1 = EvalSystem::digest($root, $bytes, $offset);
        // $obj_index2 = EvalSystem::digest($root, $bytes, $offset);
        // $obj_idxr = AstFactory::bindexerNode($obj, $obj_index1, $obj_index2);
        $score = EvalSystem::digest($root, $bytes, $offset);
        $track = EvalSystem::digest($root, $bytes, $offset);
        $obj_index = AstFactory::scoreTrackNode($score, $track);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);
        // 
        $prop = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        // $node->prop = $prop;

        switch ($prop) {
            case 0x20:
            case 0x24:
            case 0x25:
            case 0x26:
            case 0x27:
            case 0x28:
            case 0x29:
            case 0x2A:
            case 0x2B:
            case 0x2C:
                // 3dlocation?
                break;
            case 0x21:
                break;
            case 0x22:
            case 0x23:
                break;
            case 0x30:
            case 0x31:
                break;
        }

        switch ($prop) {
            // ??
        }

        $propname = ScriptTrackProp::names[$prop]??''; 
        $propValType = ScriptTrackProp::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

        $obj_idxr_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);

        return $obj_idxr_prop;
    }

}