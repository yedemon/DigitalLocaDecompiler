<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptTrackProp {

    const names = [
        17 => 'Puppet',
        32 => 'Variable.Visible',
        33 => 'Variable.CastNumber',
        34 => 'Variable.IntX',
        35 => 'Variable.IntY',
        36 => 'Variable.Pos.X',
        37 => 'Variable.Pos.Y',
        38 => 'Variable.Pos.Z',
        39 => 'Variable.Rol.X',
        40 => 'Variable.Rol.Y',
        41 => 'Variable.Rol.Z',
        42 => 'Variable.Scl.X',
        43 => 'Variable.Scl.Y',
        44 => 'Variable.Scl.Z',
        46 => 'Variable.AlphaADD',

        48 => 'Variable.SizX',
        49 => 'Variable.SizY',

        0x10 => 'Tag',
        0x40 => 'Score.Visible',
        0x44 => 'Score.Pos.X',
        0x45 => 'Score.Pos.Y',
        0x46 => 'Score.Pos.Z',
        0x47 => 'Score.Rol.X',
        0x48 => 'Score.Rol.Y',
        0x49 => 'Score.Rol.Z',
        0x4A => 'Score.Scl.X',
        0x4B => 'Score.Scl.Y',
        0x4C => 'Score.Scl.Z',
        0x41 => 'Score.CastNumber',
        0x42 => 'Score.IntX',
        0x43 => 'Score.IntY',
        0x50 => 'Score.SizX',
        0x51 => 'Score.SizY',

        0x54 => 'Score.Rol2D',
        0x4D => 'Score.Alpha',
        0x4E => 'Score.AlphaAdd',
        0x4F => 'Score.Filter',

        0x34 => 'Variable.Rol2D',
        0x2D => 'Variable.Alpha',
        0x2F => 'Variable.Filter',

        0x8A => 'Variable.Material.Model.Alpha',
        0x8B => 'Variable.Material.Model.Blend',
        0x80 => 'Variable.Material.Model.Diffuse.R',
        0x81 => 'Variable.Material.Model.Diffuse.G',
        0x82 => 'Variable.Material.Model.Diffuse.B',
        0x87 => 'Variable.Material.Model.Emit.R',
        0x88 => 'Variable.Material.Model.Emit.G',
        0x89 => 'Variable.Material.Model.Emit.B',
        0x86 => 'Variable.Material.Model.Shine',
        0x83 => 'Variable.Material.Model.Specular.R',
        0x84 => 'Variable.Material.Model.Specular.G',
        0x85 => 'Variable.Material.Model.Specular.B',
        0x8C => 'Variable.Material.Light.Color.R',
        0x8D => 'Variable.Material.Light.Color.G',
        0x8E => 'Variable.Material.Light.Color.B',
        0x90 => 'Variable.Material.Light.CutOffAngle',
        0x91 => 'Variable.Material.Light.CutOffAnglePhi',
        0x92 => 'Variable.Material.Light.Distance',
        0x8F => 'Variable.Material.Light.DropOffRate',
        0x94 => 'Variable.Material.Camera.BackClip',
        0x99 => 'Variable.Material.Camera.Fog.Back',
        0x95 => 'Variable.Material.Camera.Fog.Color.R',
        0x96 => 'Variable.Material.Camera.Fog.Color.G',
        0x97 => 'Variable.Material.Camera.Fog.Color.B',
        0x98 => 'Variable.Material.Camera.Fog.Fore',
        0x93 => 'Variable.Material.Camera.ZoomFactor',

        0x36 => 'Variable.UDiv',
        0x37 => 'Variable.VDiv',
        0x38 => 'Variable.UOfs',
        0x39 => 'Variable.VOfs',

        // 0x13 0x23 => 'Wave.Frequency',
    ];

    const valTypes = [
        17 => PCodeReader::VAL_BOOL,
        32 => PCodeReader::VAL_BOOL,
        33 => PCodeReader::VAL_INT,
        34 => PCodeReader::VAL_INT,
        35 => PCodeReader::VAL_INT,
        36 => PCodeReader::VAL_FLOAT,
        37 => PCodeReader::VAL_FLOAT,
        38 => PCodeReader::VAL_FLOAT,
        39 => PCodeReader::VAL_FLOAT,
        40 => PCodeReader::VAL_FLOAT,
        41 => PCodeReader::VAL_FLOAT,
        42 => PCodeReader::VAL_FLOAT,
        43 => PCodeReader::VAL_FLOAT,
        44 => PCodeReader::VAL_FLOAT,
        46 => PCodeReader::VAL_BOOL,

        48 => PCodeReader::VAL_INT,
        49 => PCodeReader::VAL_INT,

        0x10 => PCodeReader::VAL_INT,
        0x40 => PCodeReader::VAL_BOOL,
        0x44 => PCodeReader::VAL_FLOAT,
        0x45 => PCodeReader::VAL_FLOAT,
        0x46 => PCodeReader::VAL_FLOAT,
        0x47 => PCodeReader::VAL_FLOAT,
        0x48 => PCodeReader::VAL_FLOAT,
        0x49 => PCodeReader::VAL_FLOAT,
        0x4A => PCodeReader::VAL_FLOAT,
        0x4B => PCodeReader::VAL_FLOAT,
        0x4C => PCodeReader::VAL_FLOAT,
        0x41 => PCodeReader::VAL_INT,
        0x42 => PCodeReader::VAL_INT,
        0x43 => PCodeReader::VAL_INT,
        0x50 => PCodeReader::VAL_INT,
        0x51 => PCodeReader::VAL_INT,

        0x54 => PCodeReader::VAL_FLOAT,
        0x4D => PCodeReader::VAL_INT,
        0x4E => PCodeReader::VAL_BOOL,
        0x4F => PCodeReader::VAL_BOOL,

        0x34 => PCodeReader::VAL_FLOAT,
        0x2D => PCodeReader::VAL_INT,
        0x2F => PCodeReader::VAL_BOOL,

        0x8A => PCodeReader::VAL_INT,
        0x8B => PCodeReader::VAL_INT,
        0x80 => PCodeReader::VAL_INT,
        0x81 => PCodeReader::VAL_INT,
        0x82 => PCodeReader::VAL_INT,
        0x87 => PCodeReader::VAL_INT,
        0x88 => PCodeReader::VAL_INT,
        0x89 => PCodeReader::VAL_INT,
        0x86 => PCodeReader::VAL_INT,
        0x83 => PCodeReader::VAL_INT,
        0x84 => PCodeReader::VAL_INT,
        0x85 => PCodeReader::VAL_INT,
        0x8C => PCodeReader::VAL_INT,
        0x8D => PCodeReader::VAL_INT,
        0x8E => PCodeReader::VAL_INT,
        0x90 => PCodeReader::VAL_INT,
        0x91 => PCodeReader::VAL_INT,
        0x92 => PCodeReader::VAL_INT,
        0x8F => PCodeReader::VAL_INT,
        0x94 => PCodeReader::VAL_INT,
        0x99 => PCodeReader::VAL_INT,
        0x95 => PCodeReader::VAL_INT,
        0x96 => PCodeReader::VAL_INT,
        0x97 => PCodeReader::VAL_INT,
        0x98 => PCodeReader::VAL_INT,
        0x93 => PCodeReader::VAL_INT,

        0x36 => PCodeReader::VAL_FLOAT,
        0x37 => PCodeReader::VAL_FLOAT,
        0x38 => PCodeReader::VAL_FLOAT,
        0x39 => PCodeReader::VAL_FLOAT,
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::TrackPropertyNode();
        // $node = new ScriptTrackProp();
        // $node->op = 'TrackProperty';

        // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
        // $node->nodes[] = EvalSystem::digest($root, $bytes, $offset);
        // $obj_index1 = EvalSystem::digest($root, $bytes, $offset);
        // $obj_index2 = EvalSystem::digest($root, $bytes, $offset);
        // $obj_idxr = AstFactory::bindexerNode($obj, $obj_index1, $obj_index2);
        $score = EvalSystem::digest($root, $bytes, $offset);
        $track = EvalSystem::digest($root, $bytes, $offset);
        $obj_index = AstFactory::scoreTrackNode($score, $track);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);

        $node2 = null;
        switch ($prop) {
            case 0x10:
            case 0x11:
            case 0x12:
                $node2 = EvalSystem::digest($root, $bytes, $offset);
                break;

            case 0x13:
                // trackwave..
                throw new \Exception('wave in trackproperty.');

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
            case 0x2B:
            case 0x2C:
            case 0x2D:
            case 0x2E:
            case 0x2F:
                $node2 = EvalSystem::digest($root, $bytes, $offset);
                break;

            case 0x30:
            case 0x31:
                $node2 = EvalSystem::digest($root, $bytes, $offset);
                break;

            case 0x80:
            case 0x81:
            case 0x82:
            case 0x83:
            case 0x84:
            case 0x85:
            case 0x86:
            case 0x87:
            case 0x88:
            case 0x89:
            case 0x8A:
            case 0x8B:
            case 0x8C:
            case 0x8D:
            case 0x8E:
            case 0x8F:
                $node2 = EvalSystem::digest($root, $bytes, $offset);
                break;

            case 0x90:
            case 0x91:
            case 0x92:
            case 0x93:
            case 0x94:
            case 0x95:
            case 0x96:
            case 0x97:
            case 0x98:
            case 0x99:
                $node2 = EvalSystem::digest($root, $bytes, $offset);
                break;
        }

        $propname = self::names[$prop]??''; 
        $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

        $obj_idxr_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
        $node = AstFactory::assignNode($obj_idxr_prop, $node2);
        return $node;
    }
}
