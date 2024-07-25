<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

class ScriptTextureCast {

    public function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::TextureCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);

        


        return $node;
    }

}