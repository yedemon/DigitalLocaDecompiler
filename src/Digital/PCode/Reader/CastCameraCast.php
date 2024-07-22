<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class CastCameraCast {

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::CameraCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset); 
        // $node->prop = $prop;

        switch ($prop) {
            // ??
        }

        $propname = ScriptCameraCast::names[$prop]??'';
        $propValType = ScriptCameraCast::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

        $obj_idxr_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);

        return $obj_idxr_prop;
    }

}