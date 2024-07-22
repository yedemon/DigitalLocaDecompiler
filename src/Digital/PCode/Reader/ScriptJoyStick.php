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
        0x11 => 'Enabled',
        0x12 => 'DigitalX',
        0x13 => 'DigitalY',
        0x14 => 'DigitalZ',
        0x1A => 'DigitalR',
        0x1B => 'DigitalU',
        0x1C => 'DigitalV',

        0x15 => 'AnalogXPos',
        0x16 => 'AnalogYPos',
        0x17 => 'AnalogZPos',
        0x1D => 'AnalogRPos',
        0x1E => 'AnalogUPos',
        0x1F => 'AnalogVPos',

        0x20 => 'POV',
        0x18 => 'ButtonCount',
    ];

    const valTypes = [
        0x11 => PCodeReader::VAL_BOOL,
        0x12 => PCodeReader::VAL_INT,
        0x13 => PCodeReader::VAL_INT,
        0x14 => PCodeReader::VAL_INT,
        0x1A => PCodeReader::VAL_INT,
        0x1B => PCodeReader::VAL_INT,
        0x1C => PCodeReader::VAL_INT,

        0x15 => PCodeReader::VAL_INT,
        0x16 => PCodeReader::VAL_INT,
        0x17 => PCodeReader::VAL_INT,
        0x1D => PCodeReader::VAL_INT,
        0x1E => PCodeReader::VAL_INT,
        0x1F => PCodeReader::VAL_INT,

        0x20 => PCodeReader::VAL_INT,
        0x18 => PCodeReader::VAL_INT,
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