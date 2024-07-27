<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\PCode\PCodeReader;

class ScriptTextureCast {
    
    const names = [
        0x41 => 'Height',
        0x12 => 'Name',
        0x10 => 'Tag',
        0x40 => 'Width',
        0x26 => 'LoadInfo.AlphaEnabled',
        0x30 => 'LoadInfo.ColorKey',
        0x24 => 'LoadInfo.Foreground',
        0x4A => 'Billboard.Alpha',
        0x49 => 'Billboard.AlphaType',
        0x54 => 'Billboard.ClippingValue',
        0x56 => 'Billboard.CenterX',
        0x57 => 'Billboard.CenterY',
        0x4B => 'Billboard.Color.R',
        0x4C => 'Billboard.Color.G',
        0x4D => 'Billboard.Color.B',
        0x52 => 'Billboard.FixSize',
        0x48 => 'Billboard.Height',
        0x4E => 'Billboard.Material',
        0x58 => 'Billboard.OffsetZ',
        0x47 => 'Billboard.Width',
        0x53 => 'Billboard.ZBufferMode',
        0x25 => 'LoadInfo.CreateMipmap',
        0x2A => 'LoadInfo.Billboard.Alpha',
        0x29 => 'LoadInfo.Billboard.AlphaType',
        0x34 => 'LoadInfo.Billboard.ClippingValue',
        0x36 => 'LoadInfo.Billboard.CenterX',
        0x37 => 'LoadInfo.Billboard.CenterY',
        0x2B => 'LoadInfo.Billboard.Color.R',
        0x2C => 'LoadInfo.Billboard.Color.G',
        0x2D => 'LoadInfo.Billboard.Color.B',
        0x43 => 'LoadInfo.Billboard.FixSize',
        0x28 => 'LoadInfo.Billboard.Height',
        0x2E => 'LoadInfo.Billboard.Material',
        0x38 => 'LoadInfo.Billboard.OffsetZ',
        0x27 => 'LoadInfo.Billboard.Width',
        0x33 => 'LoadInfo.Billboard.ZBufferMode',
    ];

    const valTypes = [
        0x41 => PCodeReader::VAL_INT,
        0x12 => PCodeReader::VAL_STRING,
        0x10 => PCodeReader::VAL_INT,
        0x40 => PCodeReader::VAL_INT,
        0x26 => PCodeReader::VAL_BOOL,
        0x30 => PCodeReader::VAL_INT,
        0x24 => PCodeReader::VAL_BOOL,
        0x4A => PCodeReader::VAL_FLOAT,
        0x49 => PCodeReader::VAL_INT,
        0x54 => PCodeReader::VAL_FLOAT,
        0x56 => PCodeReader::VAL_FLOAT,
        0x57 => PCodeReader::VAL_FLOAT,
        0x4B => PCodeReader::VAL_FLOAT,
        0x4C => PCodeReader::VAL_FLOAT,
        0x4D => PCodeReader::VAL_FLOAT,
        0x52 => PCodeReader::VAL_BOOL,
        0x48 => PCodeReader::VAL_FLOAT,
        0x4E => PCodeReader::VAL_BOOL,
        0x58 => PCodeReader::VAL_FLOAT,
        0x47 => PCodeReader::VAL_FLOAT,
        0x53 => PCodeReader::VAL_INT,
        0x25 => PCodeReader::VAL_BOOL,
        0x2A => PCodeReader::VAL_FLOAT,
        0x29 => PCodeReader::VAL_INT,
        0x34 => PCodeReader::VAL_FLOAT,
        0x36 => PCodeReader::VAL_FLOAT,
        0x37 => PCodeReader::VAL_FLOAT,
        0x2B => PCodeReader::VAL_FLOAT,
        0x2C => PCodeReader::VAL_FLOAT,
        0x2D => PCodeReader::VAL_FLOAT,
        0x43 => PCodeReader::VAL_BOOL,
        0x28 => PCodeReader::VAL_FLOAT,
        0x2E => PCodeReader::VAL_BOOL,
        0x38 => PCodeReader::VAL_FLOAT,
        0x27 => PCodeReader::VAL_FLOAT,
        0x33 => PCodeReader::VAL_INT,
    ];

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

        if ($prop <= 0x45) {
            if ($prop == 0x45) {
                $node = AstFactory::callNode($obj_idxr, 'X45', [], 0x45);
                goto LABEL_347;
            }
            if ($prop <= 0x35) {
                switch ($prop) {
                    case 0x35:
                        // something unknown.
                        $node = AstFactory::callNode($obj_idxr, 'X35', [], 0x35);
                        break;
                    case 0x13:
                        $node = AstFactory::callNode($obj_idxr, 'X13', [], 0x13);
                        goto LABEL_347;
                    case 0x20:
                        $params = [];
                        $params[] = EvalSystem::digest($root, $bytes, $offset);
                        $node = AstFactory::callNode($obj_idxr, 'Load', $params, 0x20);
                        break;
                    case 0x31:
                        // something unknown.
                        $node = AstFactory::callNode($obj_idxr, 'X31', [], 0x31);
                        goto LABEL_347;
                    default:
                        goto LABEL_120;
                }
                goto LABEL_347;
            }
            if ($prop == 0x42) {
                // LoadFromTextCast
                $params = [];
                $params[] = EvalSystem::digest($root, $bytes, $offset);// text cast
                $params[] = EvalSystem::digest($root, $bytes, $offset);// option
                $params[] = EvalSystem::digest($root, $bytes, $offset);// EdgeColor

                $params[] = EvalSystem::digest($root, $bytes, $offset);//MerginLeft
                $params[] = EvalSystem::digest($root, $bytes, $offset);//MerginRight
                $params[] = EvalSystem::digest($root, $bytes, $offset);//MerginTop
                $params[] = EvalSystem::digest($root, $bytes, $offset);//MerginBottom

                $node = AstFactory::callNode($obj_idxr, 'LoadFromTextCast', $params, 0x42);
                goto LABEL_347;
            }
            if ($prop == 0x44) {
                $node2 = EvalSystem::digest($root, $bytes, $offset); // somestring in higher version?

                $propname = self::names[$prop]??'';
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_STRING;

                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = AstFactory::assignNode($obj_prop, $node2, true);
                goto LABEL_347;
            }
            goto LABEL_120;
        }
        if ($prop <= 0x51) {
            switch ($prop) {
                case 0x51:
                    // somestring in higher version?
                    $params = [];
                    $params[] = EvalSystem::digest($root, $bytes, $offset);
                    $params[] = EvalSystem::digest($root, $bytes, $offset);
                    $params[] = EvalSystem::digest($root, $bytes, $offset);
                    $params[] = EvalSystem::digest($root, $bytes, $offset);
                    $node = AstFactory::callNode($obj_idxr, 'X51', $params, 0x51);
                    // if !v7?
                    break;
                case 0x46:
                    $node = AstFactory::callNode($obj_idxr, 'X46', [], 0x46);
                    goto LABEL_347;
                case 0x50:
                    $params = [];
                    $params[] = EvalSystem::digest($root, $bytes, $offset);
                    $params[] = EvalSystem::digest($root, $bytes, $offset);
                    $node = AstFactory::callNode($obj_idxr, 'X50', $params, 0x50);
                    break;
                default:
                    goto LABEL_120;
            }

            goto LABEL_347;
        }
        if ($prop == 0x55) {
            $node = AstFactory::callNode($obj_idxr, 'Reset', [], 0x55);
            goto LABEL_347;
        }

        if ( $prop != 0x60 )
        {
LABEL_120:
            if ($this->script_main) {
                // assign prop.
                $node2 = EvalSystem::digest($root, $bytes, $offset);

                $propname = self::names[$prop]??'';
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = AstFactory::assignNode($obj_prop, $node2);
                // switch($prop) {
                //     default:
                //         goto LABEL_346;
                // }
            }
            else {
                $propname = self::names[$prop]??'';
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = $obj_prop;
                // get prop.
//                 switch($prop) {
//                     default:
// LABEL_346:
//                         throw new Exception('SCRIPT RUNTIME ERROR');
//                 }
            }
            goto LABEL_347;
        }
        // 0x60
        {
            if (!$this->script_main) {
                // get prop.
                $propname = self::names[$prop]??'';
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = $obj_prop;
                
                goto LABEL_347;
            } else {
                // assign prop.
                $node2 = EvalSystem::digest($root, $bytes, $offset);

                $propname = self::names[$prop]??'';
                $propValType = self::valTypes[$prop]??PCodeReader::VAL_UNKNOWN;

                $obj_prop = AstFactory::propNode($obj_idxr, $prop, $propname, $propValType);
                $node = AstFactory::assignNode($obj_prop, $node2);
            }
        }

LABEL_347:
        return $node;
    }

}
