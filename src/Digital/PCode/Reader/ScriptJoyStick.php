<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;
use Exception;

class ScriptJoyStick {

    const names = [
        18 => 'DigitalX',
        19 => 'DigitalY',
    ];

    const valTypes = [
        18 => PCodeReader::VAL_INT,
        19 => PCodeReader::VAL_INT,
    ];

    public static function getJoyStickIndexAlias(AstNode $obj_index) : string {
        $literal=$obj_index->getLiteralValue();
        if ($literal === null) return '';
        if (!is_numeric($literal)) return '';

        return 'JOYSTICKID' . ($literal + 1);
    }

    public static function getJoyStickButtonAlias(AstNode $obj_index) : string {
        $literal=$obj_index->getLiteralValue();

        if ($literal === null) return '';
        if (!is_numeric($literal)) return '';

        return 'JOY_BUTTON' . ($literal + 1);
    }

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::JoyStickNode();
        
        $prop = d_u1($bytes, $offset);
        if ($prop != 0x10) {
            throw new Exception('Joystick only have method 0x10 at'.$offset);
        }

        // $node->prop = $prop;
        // $node->op = 'Capture';
        $node = AstFactory::callNode($obj, 'Capture', [], $prop);
        return $node;
    }

}