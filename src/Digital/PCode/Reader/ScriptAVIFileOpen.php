<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

class ScriptAVIFileOpen {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $node = AstFactory::AVIFileNode();

        return $node;
    }

}