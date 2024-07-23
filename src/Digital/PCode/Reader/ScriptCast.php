<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

/**
 * 0xF1
 */
class ScriptCast {

    /** TrackProp */
    const TrackProp = 0x70;

    /** CollisionCheck */
    const CollisionCheck = 0x71;

    /** GetCrossPoint */
    const GetCrossPoint = 0x72;

    const WorldToScreen = 0x74;

    const WorldToScreenEx = 0x7D;

    /** CollisionCheckEx */
    const CollisionCheckEx = 0x76;

    /** ModelCast */
    const ModelCast = 0x80;

    /** wv */
    const WaveCast = 0x84;

    // camera cast....
    const CameraCast = 0x87;

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $cmd = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        
        switch($cmd) {
            case self::TrackProp:
                $node = CastTrackProp::digest($root, $bytes, $offset);
                break;

            case self::CollisionCheck:
                $node = static::digestCollisionCheck($root, $bytes, $offset);
                break;

            case self::GetCrossPoint:
                $node = static::digestGetCrossPoint(0x72, $root, $bytes, $offset);
                break;

            case self::WorldToScreen:
                $node = static::digestWorldToScreenEx(0x74, $root, $bytes, $offset);
                break;

            case self::WorldToScreenEx:
                $node = static::digestWorldToScreenEx(0x7D, $root, $bytes, $offset);
                break;

            case self::CollisionCheckEx:
                $node = static::digestCollisionCheckEx($root, $bytes, $offset);
                break;

            case self::ModelCast:
                $node = CastModelCast::digest($root, $bytes, $offset);
                break;

            case self::WaveCast:
                $node = CastWaveCast::digest($root, $bytes, $offset);
                break;

            case self::CameraCast:
                $node = CastCameraCast::digest($root, $bytes, $offset);
                break;
        }

        return $node;
    }

    private static function digestGetCrossPoint($flag, AstRoot $root, $bytes, &$offset) {
        // $node->op = 'GetCrossPoint';
        $params = [];
        
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset);
        
        //??    sub_489E80*6
        $params[] = EvalSystem::digest($root, $bytes, $offset);//score
        $params[] = EvalSystem::digest($root, $bytes, $offset);//track
        $params[] = EvalSystem::digest($root, $bytes, $offset);//flag

        // gettrack...
        if ($flag == 0x10) {
            // throw new Exception('GetCrossPoint 0x16 at', $offset);
            // GetCrossPointEx
            $params[] = EvalSystem::digest($root, $bytes, $offset); //LowerGroup
            $params[] = EvalSystem::digest($root, $bytes, $offset); //UpperGroup

            $node = AstFactory::syscallNode('GetCrossPointEx', $params, 0x3F);
        } else {
            $node = AstFactory::syscallNode('GetCrossPoint', $params, self::GetCrossPoint);
        }

        return $node;
    }

    private static function digestCollisionCheck(AstRoot $root, $bytes, &$offset) {
        // $node->op = 'CollisionCheck';
        $params = [];

        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);

        $node = AstFactory::syscallNode('CollisionCheck', $params, self::CollisionCheck);
        return $node;
    }

    private static function digestCollisionCheckEx(AstRoot $root, $bytes, &$offset) {
        // $node->op = 'CollisionCheck';
        $params = [];

        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);

        $params[] = EvalSystem::digest($root, $bytes, $offset);

        $node = AstFactory::syscallNode('CollisionCheckEx', $params, self::CollisionCheckEx);
        return $node;
    }

    private static function digestWorldToScreenEx($cmd, AstRoot $root, $bytes, &$offset) {
        $params = [];
        if ($cmd == self::WorldToScreen) {
        } else {
            $params[] = EvalSystem::digest($root, $bytes, $offset);
        }

        $params[] = EvalSystem::digest($root, $bytes, $offset); //WX
        $params[] = EvalSystem::digest($root, $bytes, $offset); //WY
        $params[] = EvalSystem::digest($root, $bytes, $offset); //WZ

        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //SX
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //SY
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //SZ float

        if ($cmd == self::WorldToScreen) {
            $node = AstFactory::syscallNode('WorldToScreen', $params, self::WorldToScreen);
        } else {
            $node = AstFactory::syscallNode('WorldToScreenEx', $params, self::WorldToScreenEx);
        }

        return $node;
    }
}
