<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptCameraCast {

    const names = [
        0x2A => 'Angle',
        0x23 => 'BackClip',
        0x29 => 'Fog.Back',
        0x25 => 'Fog.Color.R',
        0x26 => 'Fog.Color.G',
        0x27 => 'Fog.Color.B',
        0x24 => 'Fog.Enabled',
        0x28 => 'Fog.Fore',
        0x22 => 'FrontClip',
        0x12 => 'Name',
        0x10 => 'Tag',
        0x21 => 'ZoomFactor',
    ];

    const valTypes = [
        0x2A => PCodeReader::VAL_FLOAT,
        0x23 => PCodeReader::VAL_FLOAT,
        0x29 => PCodeReader::VAL_FLOAT,
        0x25 => PCodeReader::VAL_INT,
        0x26 => PCodeReader::VAL_INT,
        0x27 => PCodeReader::VAL_INT,
        0x24 => PCodeReader::VAL_BOOL,
        0x28 => PCodeReader::VAL_INT,
        0x22 => PCodeReader::VAL_FLOAT,
        0x12 => PCodeReader::VAL_STRING,
        0x10 => PCodeReader::VAL_INT,
        0x21 => PCodeReader::VAL_FLOAT,
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::CameraCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);
        // $node->prop = $prop;

        if ($prop != 0x11) {
            $node2 = EvalSystem::digest($root, $bytes, $offset);

            $propname = self::names[$prop]??'';
            $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

            $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
            $node = AstFactory::assignNode($obj_prop, $node2);
        } else {
            //Reset
            $node = AstFactory::callNode($obj_idxr, 'Reset', [], 0x11);
        }

        switch ( $prop )
        {
            case 0x10:
            // case 0x11:
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