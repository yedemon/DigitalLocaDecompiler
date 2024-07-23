<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

// special node.
class ScriptViewport {

    const names = [
        0x19 => 'BackColor.B',
        0x18 => 'BackColor.G',
        0x17 => 'BackColor.B',
        0x29 => 'BackColor.Value',

        0x1D => 'BG2DBitmap',
        0x22 => 'BG2DMovie',
        0x1E => 'BG2DOfsX',
        0x1F => 'BG2DOfsY',
        0x20 => 'BG2DScaleH',
        0x21 => 'BG2DScaleV',

        0x26 => 'BG3DMode',
        0x1C => 'BG3DOfsY',
        0x1B => 'BG3DTexture',
        0x14 => 'Bottom',
        0x10 => 'CameraTrack',
        0x16 => 'Enabled',
        0x1A => 'FillType',
        0x23 => 'HFlip',

        0x12 => 'Left',
        0x13 => 'Right',
        0x15 => 'Scaling',
        0x27 => 'Tag',
        0x11 => 'Top',
        0x24 => 'VFlip',
        0x28 => 'WireFrame',
        0x25 => 'ZBufferFill',
    ];

    const valTypes = [
        0x19 => PCodeReader::VAL_INT,
        0x18 => PCodeReader::VAL_INT,
        0x17 => PCodeReader::VAL_INT,
        0x29 => PCodeReader::VAL_INT,

        0x1D => PCodeReader::VAL_INT,
        0x22 => PCodeReader::VAL_INT,
        0x1E => PCodeReader::VAL_INT,
        0x1F => PCodeReader::VAL_INT,
        0x20 => PCodeReader::VAL_FLOAT,
        0x21 => PCodeReader::VAL_FLOAT,

        0x26 => PCodeReader::VAL_INT,
        0x1C => PCodeReader::VAL_FLOAT,
        0x1B => PCodeReader::VAL_INT,
        0x14 => PCodeReader::VAL_INT,
        0x10 => PCodeReader::VAL_UNKNOWN,
        0x16 => PCodeReader::VAL_BOOL,
        0x1A => PCodeReader::VAL_INT,
        0x23 => PCodeReader::VAL_BOOL,

        0x12 => PCodeReader::VAL_INT,
        0x13 => PCodeReader::VAL_INT,
        0x15 => PCodeReader::VAL_BOOL,
        0x27 => PCodeReader::VAL_INT,
        0x11 => PCodeReader::VAL_INT,
        0x24 => PCodeReader::VAL_BOOL,
        0x28 => PCodeReader::VAL_BOOL,
        0x25 => PCodeReader::VAL_FLOAT,
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::ViewportinfoNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $isViewPort0_3 = true;
        $obj_index_literal = $obj_idxr->getLiteralValue();
        if ($obj_index_literal !== null) {
            if ($obj_index>3) $isViewPort0_3 = false;
        }

        $prop = d_u1($bytes, $offset);
        $propNode = AstFactory::propNode($obj_idxr, $prop);

        if ($prop == 0x10) {
            $score = EvalSystem::digest($root, $bytes, $offset);
            $track = EvalSystem::digest($root, $bytes, $offset);

            $node2 = AstFactory::scoreTrackNode($score, $track);
            // $node = AstFactory::assignNode($propNode, $node2);
        } else {
            $node2 = EvalSystem::digest($root, $bytes, $offset);
        }
        
        if ($isViewPort0_3) {
            $node = AstFactory::assignNode($propNode, $node2);
        } else {
            $node = AstFactory::emptyNode();
        }

        return $node;
    }
}