<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptLightCast {

    const names = [
        33 => 'Color.R',
        34 => 'Color.G',
        35 => 'Color.B',
    ];

    const valTypes = [
        33 => PCodeReader::VAL_INT,
        34 => PCodeReader::VAL_INT,
        35 => PCodeReader::VAL_INT,
    ];
    
    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::LightCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);
        // $node->prop = $prop;

        if ($prop != 0x11) {
            $node2 = EvalSystem::digest($root, $bytes, $offset);

            $propname = self::names[$prop]??'';
            $propValType = self::valTypes[$prop]??'';

            $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
            $node = AstFactory::assignNode($obj_prop, $node2);
        } else {
            $node = AstFactory::callNode($obj_idxr, 'Reset', [], 0x11);
        }

        switch ($prop) {
            case 0x21:  //.Color.R
            case 0x22:  //.Color.G
            case 0x23:  //.Color.B
                break;
        }

        return $node;
    }

}