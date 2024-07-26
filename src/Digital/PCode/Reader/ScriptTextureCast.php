<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;

class ScriptTextureCast {

    // else is from script_cast.
    protected bool $script_main = false;

    public function __construct($script_main) {
        $this->script_main = $script_main === 1;
    }

    protected function createObjectNode() : AstNode {
        return AstFactory::TextureCastNode();
    }

    public function digest(AstRoot $root, $bytes, &$offset) : AstNode {
        $obj = self::createObjectNode();

        $obj_index = EvalSystem::digest($root, $bytes, $offset);
        $obj_idxr = AstFactory::indexerNode($obj, $obj_index);

        $prop = d_u1($bytes, $offset);

        if ($prop > 0x45) {
            if ($prop > 0x51) {
                if ($prop == 0x55) {

                }
                else {
                    if ($prop != 0x60) {
                        goto LABEL_117;
                    }
                    if (!$this->script_main) {
LABEL_283:
                        goto LABEL_284;
                    }
                    EvalSystem::digest($root, $bytes, $offset);
                }
                goto LABEL_305;
            }
            if ($prop == 0x51) {
                EvalSystem::digest($root, $bytes, $offset);
                EvalSystem::digest($root, $bytes, $offset);
                EvalSystem::digest($root, $bytes, $offset);
                EvalSystem::digest($root, $bytes, $offset);

                // BitmapCast.LoadFromTextCast
                goto LABEL_284;
            }
            if ($prop == 0x46) {
                goto LABEL_305;
            }

            if ($prop != 0x50) {
                goto LABEL_117;
            }
            
            EvalSystem::digest($root, $bytes, $offset);
            EvalSystem::digest($root, $bytes, $offset);
// LABEL_64:
            goto LABEL_284;
        }
        if ($prop == 0x45) {
LABEL_290:
            goto LABEL_305;
        }

        if ($prop > 0x35) {
            if ($prop != 0x42) {
                if ($prop != 0x44) {
                    goto LABEL_117;
                }

                goto LABEL_305;
            }

            goto LABEL_284;
        }

        switch ($prop) {
            case 0x35:
                break;
            case 0x13:
                break;
            case 0x20:
                EvalSystem::digest($root, $bytes, $offset);
                break;
            case 0x31:
LABEL_284:
                break;

            default:
LABEL_117:
                if (!$this->script_main) {
                    switch ($prop) {
                        case 0x10:
                        case 0x29:
                        case 0x30:
                        case 0x33:
                            goto LABEL_290;
                        case 0x12:
                            goto LABEL_305;
                        case 0x24:
                        case 0x25:
                        case 0x26:
                        case 0x2E:
                        case 0x32:
                        case 0x39:
                        case 0x3B:
                        case 0x43:
                            goto LABEL_284;

                        case 0x27:
                            goto LABEL_305;

                        case 0x28:
                            goto LABEL_305;

                        case 0x2A:
                            goto LABEL_305;

                        case 0x2B:
                            goto LABEL_305;

                        case 0x2C:
                            goto LABEL_305;
                            
                        case 0x2D:
                            goto LABEL_305;

                        case 0x40:
                            goto LABEL_290;

                        case 0x4E:
                            goto LABEL_283;

                        case 0x52:
                            goto LABEL_283;

                        default:
LABEL_304:
                            goto LABEL_305;
                    }
                }
                EvalSystem::digest($root, $bytes, $offset);
                switch ($prop) {
                    default:
                        goto LABEL_304;
                }
        }
LABEL_305:

        return $node;
    }

}