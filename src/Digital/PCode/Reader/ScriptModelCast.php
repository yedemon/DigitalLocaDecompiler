<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptModelCast {

    const names = [
        80 => 'Alpha',
        81 => 'AplhaAdd',
        0x52 => 'Blend',
        0x53 => 'BlendMode',
        0x30 => 'Diffuse.R',
        0x31 => 'Diffuse.G',
        0x32 => 'Diffuse.B',
        0x36 => 'Emit.R',
        0x37 => 'Emit.G',
        0x38 => 'Emit.B',
        0x93 => 'FillMode',
        0x90 => 'Light.Channels',
        0x12 => 'name',
        0x39 => 'Shine',
        0x33 => 'Specular.R',
        0x34 => 'Specular.G',
        0x35 => 'Specular.B',
        0x10 => 'Tag',
        0x20 => 'Texture',
        0x54 => 'Texture2',
        0x3A => 'UDiv',
        0x3B => 'VDiv',
        0x3C => 'UOfs',
        0x3D => 'VOfs',
        0x69 => 'XMin',
        0x6A => 'YMin',
        0x6B => 'ZMin',
        0x6C => 'XMax',
        0x6D => 'YMax',
        0x6E => 'ZMax',
        0x3E => 'XRol',
        0x3F => 'YRol',
        0x40 => 'ZRol',
        0x55 => 'UDiv2',
        0x56 => 'VDiv2',
        0x57 => 'UOfs2',
        0x58 => 'VOfs2',
        0x59 => 'XRol2',
        0x5A => 'YRol2',
        0x5B => 'ZRol2',
        0x5D => 'ModelClipping',
        0x5C => 'GroupClipping',
        0x5E => 'UseModelClipping',
    ];

    const valTypes = [
        80 => PCodeReader::VAL_INT,
        81 => PCodeReader::VAL_BOOL,
        0x52 => PCodeReader::VAL_INT,
        0x53 => PCodeReader::VAL_INT,
        0x30 => PCodeReader::VAL_INT,
        0x31 => PCodeReader::VAL_INT,
        0x32 => PCodeReader::VAL_INT,
        0x36 => PCodeReader::VAL_INT,
        0x37 => PCodeReader::VAL_INT,
        0x38 => PCodeReader::VAL_INT,
        0x93 => PCodeReader::VAL_INT,
        0x90 => PCodeReader::VAL_INT,
        0x12 => PCodeReader::VAL_STRING,
        0x39 => PCodeReader::VAL_FLOAT,
        0x33 => PCodeReader::VAL_INT,
        0x34 => PCodeReader::VAL_INT,
        0x35 => PCodeReader::VAL_INT,
        0x10 => PCodeReader::VAL_INT,
        0x20 => PCodeReader::VAL_INT,
        0x54 => PCodeReader::VAL_INT,
        0x3A => PCodeReader::VAL_FLOAT,
        0x3B => PCodeReader::VAL_FLOAT,
        0x3C => PCodeReader::VAL_FLOAT,
        0x3D => PCodeReader::VAL_FLOAT,
        0x69 => PCodeReader::VAL_INT,
        0x6A => PCodeReader::VAL_INT,
        0x6B => PCodeReader::VAL_INT,
        0x6C => PCodeReader::VAL_INT,
        0x6D => PCodeReader::VAL_INT,
        0x6E => PCodeReader::VAL_INT,
        0x3E => PCodeReader::VAL_FLOAT,
        0x3F => PCodeReader::VAL_FLOAT,
        0x40 => PCodeReader::VAL_FLOAT,
        0x55 => PCodeReader::VAL_FLOAT,
        0x56 => PCodeReader::VAL_FLOAT,
        0x57 => PCodeReader::VAL_FLOAT,
        0x58 => PCodeReader::VAL_FLOAT,
        0x59 => PCodeReader::VAL_FLOAT,
        0x5A => PCodeReader::VAL_FLOAT,
        0x5B => PCodeReader::VAL_FLOAT,
        0x5D => PCodeReader::VAL_FLOAT,
        0x5C => PCodeReader::VAL_FLOAT,
        0x5E => PCodeReader::VAL_BOOL,
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::ModelCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);
        // $node->prop = $prop;

        if ($prop == 0x80) {
            // group[xx];
            $obj_group_index = EvalSystem::digest($root, $bytes, $offset);

            $obj_idxr_group = AstFactory::subobjNode($obj_idxr, 'Group', 0x80);
            $obj_idxr_group_idxr = AstFactory::indexerNode($obj_idxr_group, $obj_group_index);

            // 0x11 reset..
            $prop0 = d_u1($bytes, $offset);
            if ($prop0 != 0x11) {
                // $cnode = new AstNode();
                // $cnode->op = cmdhex($prop0);
                $node2 = EvalSystem::digest($root, $bytes, $offset);
                // $node = AstFactory::assignNode($obj_group_index, $node2);

                $prop0name = self::names[$prop0]??''; 
                $prop0ValType = self::valTypes[$prop0]??PCodeReader::VAL_UNKNOWN;

                $obj_group_prop = AstFactory::propNode($obj_idxr_group_idxr, $prop0, $prop0name, $prop0ValType);
                $node = AstFactory::assignNode($obj_group_prop, $node2);
            } else {
                $node = AstFactory::callNode($obj_idxr_group_idxr, 'Reset', [], 0x11);
            }

            // once..
            // 4B13E4

        } else {
            if ($prop != 0x11) {
                $node2 = EvalSystem::digest($root, $bytes, $offset);

                $propname = self::names[$prop]??''; 
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;
                
                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = AstFactory::assignNode($obj_prop, $node2);
            } else {
                $node = AstFactory::callNode($obj_idxr, 'Reset', [], 0x11);
            }

            // cycle..
            // 4B13E4
        }

        return $node;
    }

}