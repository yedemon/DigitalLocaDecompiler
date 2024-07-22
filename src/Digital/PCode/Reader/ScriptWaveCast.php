<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptWaveCast {

    const names = [
        0x22 => 'Pause',
        0x28 => 'PauseOnly',
        0x20 => 'Play',
        0x11 => 'Reset',
        0x21 => 'Stop',

        0x2A => 'Length',
        0x27 => 'Playing',
        
        0x23 => 'Frequency',
        0x24 => 'Loop',
        0x2D => 'Mute',
        0x12 => 'Name',
        0x25 => 'Pan',
        0x29 => 'Position',
        0x10 => 'Tag',
        0x26 => 'Volume',
    ];

    const valTypes = [
        0x2A => PCodeReader::VAL_INT,
        0x27 => PCodeReader::VAL_BOOL,
        
        0x23 => PCodeReader::VAL_INT,
        0x24 => PCodeReader::VAL_BOOL,
        0x2D => PCodeReader::VAL_BOOL,
        0x12 => PCodeReader::VAL_STRING,
        0x25 => PCodeReader::VAL_INT,
        0x29 => PCodeReader::VAL_INT,
        0x10 => PCodeReader::VAL_INT,
        0x26 => PCodeReader::VAL_INT,
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::WaveCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);
        // $node->prop = $prop;

        // ??
        if ($prop == 0x10 || 
            // (($prop + 0xDD)&0xFF) < 4 || // 0x23, 0x24, 0x25, 0x26
            ($prop == 0x23 || $prop == 0x24 || $prop == 0x25 || $prop == 0x26) ||
            $prop == 0x29) {
            $node2 = EvalSystem::digest($root, $bytes, $offset);

            $propname = self::names[$prop]??'';
            $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

            $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
            $node = AstFactory::assignNode($obj_prop, $node2);
        } else {
            // call
            $propname = self::names[$prop]??'';
            $node = AstFactory::callNode($obj_idxr, $propname, [], $prop);
        }

        switch ( $prop )
        {
            //??
            case 0x10:
            case 0x11:
                break;
            
            case 0x20:
            case 0x21:
            case 0x22:
            case 0x23:
            case 0x24:
            case 0x25:
            case 0x26:
                break;

            case 0x28:
            case 0x29:
                break;
            // ?
        }

        return $node;
    }

}