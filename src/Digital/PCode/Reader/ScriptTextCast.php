<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptTextCast {

    const names = [
    ];

    const valTypes = [
    ];

    public static function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = AstFactory::TextCastNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);

        if ($prop != 0x11 && $prop != 0x27 && $prop != 0x28) {
            $node2 = EvalSystem::digest($root, $bytes, $offset);

            if ($prop == 0x22) {
                // strings
                $node2 = EvalSystem::digest($root, $bytes, $offset);
            } else if ($prop == 0x25) {
                // insert
                $node3 = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::callNode($obj_idxr, 'Insert', [$node2, $node3], $prop);
            } else if ($prop == 0x26) {
                // exchange
                $node3 = EvalSystem::digest($root, $bytes, $offset);
                $node = AstFactory::callNode($obj_idxr, 'Exchange', [$node2, $node3], $prop);
            } else {
                $propname = self::names[$prop]??'';
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = AstFactory::assignNode($obj_prop, $node2);
            }
        } else {
            // call
            $propname = self::names[$prop]??'';
            $node = AstFactory::callNode($obj_idxr, $propname, [], $prop);
        }

        return $node;
    }

}