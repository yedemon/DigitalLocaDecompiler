<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

class ScriptMain {


    /** WORLDBG */
    const WorldBG = 0x20; 


    /**SetFramePerSec */
    const SetFramePerSec = 0x27;

    /** WORLD3DBG */
    const World3DBG = 0x28; 

    /** MidiOpen */
    const MidiOpen40 = 0x40;

    const MidiPlay = 0x41;

    const MidiStop = 0x43;

    const MidiAutoRepeat = 0x46;
    
    const MidiClose = 0x47;

    /** MidiOpen */
    const MidiOpen4E = 0x4E;
    
    /** WaveAudio */
    const WaveAudio = 0x50;

    /** MidiOpen */
    const MidiOpen51 = 0x51;

    /** AVIFileOpen */
    const AVIFileOpen = 0x59;

    /** BreakLoop */
    const BreakLoopEx = 0x60;

    /** Seekframe */
    const SeekFrameEx = 0x61;

    /** TrackProperty */
    const TrackProp = 0x70;

    const LocalToWorld = 0x77;

    const CameraToScreenVector = 0x7A;

    const LocalToWorldVector = 0x7B;

    const CameraToScreenVectorEx = 0x7E;

    /** ModelCast */
    const ModelCast = 0x80;

    /** TextCast */
    const TextCast = 0x83;

    /** WaveCast */
    const WaveCast = 0x84;

    /** CameraCast */
    const CameraCast = 0x87;

    /** LightCast */
    const LightCast = 0x88;

    /** Viewportinfo */
    const Viewportinfo = 0xA6;

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $cmd = d_u1($bytes, $offset); //upk0('C', substr($bytes, $offset++, 1));
        // $node->cmd = cmdhex($cmd);

        switch($cmd) {
            case self::WorldBG:
                $params = [];
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('WorldBG', $params, self::WorldBG);
                break;

            case self::SetFramePerSec:
                // $node->op = 'SetFramePerSec';
                $params = [];
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('SetFramePerSec', $params, self::SetFramePerSec);
                break;

            case self::World3DBG:
                // $node->op = 'World3DBG';
                $params = [];
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('World3DBG', $params, self::World3DBG);
                break;

            case self::MidiOpen40:
            case self::MidiOpen4E:
            case self::MidiOpen51:
                $node = static::digestMidiOpen($cmd, $root, $bytes, $offset);
                break;

            case self::MidiPlay:
                // $node->op = 'MidiPlay';
                $node = AstFactory::syscallNode('MidiPlay', [], self::MidiPlay);
                break;

            case self::MidiStop:
                // $node->op = 'MidiStop';
                $node = AstFactory::syscallNode('MidiStop', [], self::MidiStop);
                break;

            case self::MidiClose:
                // $node->op = 'MidiClose';
                $node = AstFactory::syscallNode('MidiClose', [], self::MidiClose);
                break;

            case self::MidiAutoRepeat:
                $node = static::digestMidiAutoRepeat($root, $bytes, $offset);
                break;

            case self::WaveAudio:
                $node = ScriptWaveAudio::digest($root, $bytes, $offset);
                break;
            
            case self::AVIFileOpen:
                $node = ScriptAVIFileOpen::digest($root, $bytes, $offset);
                break;

            case self::BreakLoopEx:
                $params = [];
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $params[] = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::syscallNode('BreakLoopEx', $params, self::BreakLoopEx);
                break;

            case self::SeekFrameEx:
                $node = static::digestSeekFrameEx($root, $bytes, $offset);
                break;

            case self::TrackProp:
                $node = ScriptTrackProp::digest($root, $bytes, $offset);
                break;

            case self::CameraToScreenVector:
                $node = static::digestCameraToScreenVectorEx(0x7A, $root, $bytes, $offset);
                break;

            case self::LocalToWorld:
                $node = static::digestLocalToWorld($root, $bytes, $offset);
                break;
                
            case self::CameraToScreenVectorEx:
                $node = static::digestCameraToScreenVectorEx(0x7E, $root, $bytes, $offset);
                break;

            case self::LocalToWorldVector:
                $node = static::digestLocalToWorldVector($root, $bytes, $offset);
                break;

            case self::ModelCast:
                $node = ScriptModelCast::digest($root, $bytes, $offset);
                break;

            case self::TextCast:
                $node = ScriptTextCast::digest($root, $bytes, $offset);
                break;

            case self::WaveCast:
                $node = ScriptWaveCast::digest($root, $bytes, $offset);
                break;

            case self::CameraCast:
                $node = ScriptCameraCast::digest($root, $bytes, $offset);
                break;

            case self::LightCast:
                $node = ScriptLightCast::digest($root, $bytes, $offset);
                break;

            case self::Viewportinfo:
                $node = ScriptViewport::digest($root, $bytes, $offset);
                //static::digestXA6Cast($root, $bytes, $offset);
                break;
        }

        return $node;
    }

    private static function digestMidiOpen($cmd, AstRoot $root, $bytes, &$offset) : AstNode {
        // $node->op = 'MidiOpen';
        $params = [];
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $node = AstFactory::syscallNode('MidiOpen', $params, $cmd);
        return $node;
    }

    private static function digestMidiAutoRepeat(AstRoot $root, $bytes, &$offset) : AstNode {
        // $node->op = 'MidiAutoRepeat';
        $params = [];
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $node = AstFactory::syscallNode('MidiAutoRepeat', $params, self::MidiAutoRepeat);
        return $node;
    }

    private static function digestSeekFrameEx(AstRoot $root, $bytes, &$offset) : AstNode {
        // $node->op = 'SeekFrame';
        $params = [];
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);
        $params[] = EvalSystem::digest($root, $bytes, $offset);    // true?
        $node = AstFactory::syscallNode('SeekFrameEx', $params, self::SeekFrameEx);
        return $node;
    }

    private static function digestCameraToScreenVectorEx($cmd, AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];
        if ($cmd == self::CameraToScreenVector) {
        } else {
            $params[] = EvalSystem::digest($root, $bytes, $offset); // Viewport
        }
        $params[] = EvalSystem::digest($root, $bytes, $offset); //SX
        $params[] = EvalSystem::digest($root, $bytes, $offset); //SY

        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //VX
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //VY
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //VZ

        if ($cmd == self::CameraToScreenVector) {
            $node = AstFactory::syscallNode('CameraToScreenVector', $params, self::CameraToScreenVector);
        } else {
            $node = AstFactory::syscallNode('CameraToScreenVectorEx', $params, self::CameraToScreenVectorEx);
        }

        return $node;
    }

    private static function digestLocalToWorld(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];

        $score = EvalSystem::digest($root, $bytes, $offset); //Score
        $track = EvalSystem::digest($root, $bytes, $offset); //Track
        $params[] = AstFactory::scoreTrackNode($score, $track);

        $params[] = EvalSystem::digest($root, $bytes, $offset); //LX
        $params[] = EvalSystem::digest($root, $bytes, $offset); //LY
        $params[] = EvalSystem::digest($root, $bytes, $offset); //LZ

        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //WX
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //WY
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //WZ

        $node = AstFactory::syscallNode('LocalToWorld', $params, self::LocalToWorld);
        return $node;
    }

    private static function digestLocalToWorldVector(AstRoot $root, $bytes, &$offset) : AstNode {
        $params = [];

        $score = EvalSystem::digest($root, $bytes, $offset); //Score
        $track = EvalSystem::digest($root, $bytes, $offset); //Track
        $params[] = AstFactory::scoreTrackNode($score, $track);

        $params[] = EvalSystem::digest($root, $bytes, $offset); //LX
        $params[] = EvalSystem::digest($root, $bytes, $offset); //LY
        $params[] = EvalSystem::digest($root, $bytes, $offset); //LZ

        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //WX
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //WY
        $params[] = EvalCommon::digest489CF8($root, $bytes, $offset); //WZ

        $node = AstFactory::syscallNode('LocalToWorldVector', $params, self::LocalToWorldVector);
        return $node;
    }
}
