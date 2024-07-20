<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

// special node.
class ScriptWaveAudio {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        // $node = new ScriptWaveAudio();

        $waveId = EvalSystem::digest($root, $bytes, $offset);
        // $node->nodes[] = $waveId;
        // $node->op = 'WaveAudio';
        $node = AstFactory::syscallNode('WaveAudio', [$waveId], 0x50);
        return $node;
    }

}