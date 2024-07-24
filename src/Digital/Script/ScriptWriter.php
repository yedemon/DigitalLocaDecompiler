<?php

declare(strict_types=1);

namespace Digital\Script;

use Digital\Ast\AstDecoder;
use Digital\Ast\AstFactory;
use Digital\Ast\AstNode;
use Digital\Ast\AstRoot;
use Digital\DigiLoca;
use Digital\IRCore\IRCore;

class ScriptWriter {
    const KeyTable = [
        0x1	=>'VK_LBUTTON',
        0x2	=>'VK_RBUTTON',
        0x3	=>'VK_CANCEL',
        0x4	=>'VK_MBUTTON',
        0x8	=>'VK_BACK',
        0x9	=>'VK_TAB',
        0x0C	=>'VK_CLEAR',
        0x0D	=>'VK_RETURN',
        0x10	=>'VK_SHIFT',
        0x11	=>'VK_CONTROL',
        0x12	=>'VK_MENU',
        0x13	=>'VK_PAUSE',
        0x14	=>'VK_CAPITAL',
        0x1B	=>'VK_ESCAPE',
        0x20	=>'VK_SPACE',
        0x21	=>'VK_PRIOR',
        0x22	=>'VK_NEXT',
        0x23	=>'VK_END',
        0x24	=>'VK_HOME',
        0x25	=>'VK_LEFT',
        0x26	=>'VK_UP',
        0x27	=>'VK_RIGHT',
        0x28	=>'VK_DOWN',
        0x29	=>'VK_SELECT',
        0x2C	=>'VK_SNAPSHOT',
        0x2D	=>'VK_INSERT',
        0x2E	=>'VK_DELETE',
        0x2F	=>'VK_HELP',
        0x30	=>'VK_0',
        0x31	=>'VK_1',
        0x32	=>'VK_2',
        0x33	=>'VK_3',
        0x34	=>'VK_4',
        0x35	=>'VK_5',
        0x36	=>'VK_6',
        0x37	=>'VK_7',
        0x38	=>'VK_8',
        0x39	=>'VK_9',
        0x41	=>'VK_A',
        0x42	=>'VK_B',
        0x43	=>'VK_C',
        0x44	=>'VK_D',
        0x45	=>'VK_E',
        0x46	=>'VK_F',
        0x47	=>'VK_G',
        0x48	=>'VK_H',
        0x49	=>'VK_I',
        0x4A	=>'VK_J',
        0x4B	=>'VK_K',
        0x4C	=>'VK_L',
        0x4D	=>'VK_M',
        0x4E	=>'VK_N',
        0x4F	=>'VK_O',
        0x50	=>'VK_P',
        0x51	=>'VK_Q',
        0x52	=>'VK_R',
        0x53	=>'VK_S',
        0x54	=>'VK_T',
        0x55	=>'VK_U',
        0x56	=>'VK_V',
        0x57	=>'VK_W',
        0x58	=>'VK_X',
        0x59	=>'VK_Y',
        0x5A	=>'VK_Z',
        0x5B	=>'VK_LWIN',
        0x5C	=>'VK_RWIN',
        0x5D	=>'VK_APPS',
        0x60	=>'VK_NUMPAD0',
        0x61	=>'VK_NUMPAD1',
        0x62	=>'VK_NUMPAD2',
        0x63	=>'VK_NUMPAD3',
        0x64	=>'VK_NUMPAD4',
        0x65	=>'VK_NUMPAD5',
        0x66	=>'VK_NUMPAD6',
        0x67	=>'VK_NUMPAD7',
        0x68	=>'VK_NUMPAD8',
        0x69	=>'VK_NUMPAD9',
        0x6A	=>'VK_MULTIPLY',
        0x6B	=>'VK_ADD',
        0x6C	=>'VK_SEPARATOR',
        0x6D	=>'VK_SUBTRACT',
        0x6E	=>'VK_DECIMAL',
        0x6F	=>'VK_DIVIDE',
        0x70	=>'VK_F1',
        0x71	=>'VK_F2',
        0x72	=>'VK_F3',
        0x73	=>'VK_F4',
        0x74	=>'VK_F5',
        0x75	=>'VK_F6',
        0x76	=>'VK_F7',
        0x77	=>'VK_F8',
        0x78	=>'VK_F9',
        0x79	=>'VK_F10',
        0x7A	=>'VK_F11',
        0x7B	=>'VK_F12',
        0x7C	=>'VK_F13',
        0x7D	=>'VK_F14',
        0x7E	=>'VK_F15',
        0x7F	=>'VK_F16',
        0x80	=>'VK_F17',
        0x81	=>'VK_F18',
        0x82	=>'VK_F19',
        0x83	=>'VK_F20',
        0x84	=>'VK_F21',
        0x85	=>'VK_F22',
        0x86	=>'VK_F23',
        0x87	=>'VK_F24',
        0x90	=>'VK_NUMLOCK',
        0x91	=>'VK_SCROLL',
        0xA0	=>'VK_LSHIFT',
        0xA1	=>'VK_RSHIFT',
        0xA2	=>'VK_LCONTROL',
        0xA3	=>'VK_RCONTROL',
        0xA4	=>'VK_LMENU',
        0xA5	=>'VK_RMENU',
    ];
    
    /**
     * @param ScriptDebugger $debugger
     */
    public static function valueInterpretation(DigiLoca $loca, IRCore $ircore, $debugger) {
        foreach($ircore->item_procedures as $procedure) {
            if ($debugger) {
                $debugger->onIteratingProcedure($procedure, $should_read);
                if (!$should_read)
                    continue;
            }

            AstRoot::walk_procedure($procedure, function($pnode, AstNode $node, $results) use ($loca) {
                AstFactory::markIndexerAlias($node, 
                function($path) use ($loca) {
                    // TODO: handle ?
                    $name = $loca->searchCast($path);
                    return $name;
                });

                // AstFactory::markBindexerAlias($node, 
                // function($path) use ($loca) {
                //     // TODO: handle ?
                //     [$name, $name2] = $loca->searchScoreTrack($path);
                //     return [$name, $name2];
                // });
                                
                AstFactory::markScoreTrackAlias($node,
                function($path) use ($loca) {
                    [$name, $name2] = $loca->searchScoreTrack($path);
                    return [$name, $name2];
                });

                AstFactory::markSeekframeAlias($node,
                function($path) use ($loca) {
                    // TODO: handle ?
                    [$name, $name2] = $loca->searchScoreLabel($path);
                    return [$name, $name2];
                });

                // AstFactory::markGetCrossPointAlias($node,
                // function($path) use ($loca) {
                //     [$name, $name2] = $loca->searchScoreTrack($path);
                //     return [$name, $name2];
                // });

                // AstFactory::markCollisionCheckAlias($node,
                // function($path) use ($loca) {
                //     [$name, $name2] = $loca->searchScoreTrack($path);
                //     return [$name, $name2];
                // });

                AstFactory::markBreakLoopExAlias($node,
                function($path) use ($loca) {
                    [$name, $name2] = $loca->searchScoreTrack($path);
                    return [$name, $name2];
                });

                // ModelCast[xx].Texture = 123;
                // => 
                // ModelCast[xx].Texture = ModelCast(xx);...
                AstFactory::markAssignModelCastTexture($node,
                function ($textureId) use ($loca) {
                    $name = $loca->searchTexture($textureId);
                    return $name;
                });

                // WaveAudio(123);  =>  WaveAudio(XX);...
                AstFactory::markWaveAudioAlias($node,
                function ($waveId) use ($loca) {
                    $name = $loca->searchWave($waveId);
                    return $name;
                });

                // handle GetKeyEvent keys'
                AstFactory::markGetKeyEventAlias($node,
                function ($vkId) use ($loca) {
                    $name = self::KeyTable[$vkId]??'';
                    return $name;
                });

                // handle onEvent keys'
            });
        }
    }
    
    /**
     * @param ScriptDebugger|null $debugger
     */
    public static function writeScript(IRCore $ircore, $calllback, $debugger) : array {
        // auto group scriptId, and select a scriptName

        // 1. scan onEvent procedures
        $script_table = [];
        $max_script_id = 0;
        $unindexed = 0;
        $first_usercall_script_id = -1;

        foreach($ircore->item_procedures as $procedure) {
            if ($procedure->isOnEvent()) {
                $scriptId = $procedure->getScriptId();
                $scriptName = $procedure->getScriptName();

                $script_table[$scriptId] = $scriptName;

                // have to check this function whether contains a usercall..
                // the slot should ends here.
                $hasUserCall = AstRooT::walk_procedure($procedure, function($pnode, AstNode $node, $results) {
                    if ($results == null) return false;

                    for ($i = 0; $i < count($results); $i++) {
                        if ($results[$i] === true) return true;
                    }
                    if ($node->type2 == AstFactory::UserCall) {
                        return true;
                    }
                    return false;
                });
                if ($hasUserCall && $first_usercall_script_id === -1) {
                    $first_usercall_script_id = $scriptId;
                }

                if ($scriptId > $max_script_id) {
                    $max_script_id = $scriptId;
                }
            } else {
                $unindexed ++;
            }
        }

        if (!isset($script_table[0])) {
            $script_table[0] = 'ConstVar';
        }

        if ($first_usercall_script_id === -1) {
            $slotsAvailable = $max_script_id;
        } else {
            $slotsAvailable = $first_usercall_script_id;
        }

        if ($slotsAvailable >= 1) {
            $slotsAvailable --;
            $current_slot = 1;
        } else {
            $current_slot = 0;
        }

        // funcs per slot
        $slot_size = ceil($unindexed / $slotsAvailable);

        $current_slot_fill = 0;
        $current_script = '';

        // 2. fill unnamed procedures into slots, assign scriptname
        foreach($ircore->item_procedures as $procedure) {
            if (!$procedure->isOnEvent()) {
                if ($current_slot_fill == 0) {
                    if (!isset($script_table[$current_slot])) {
                        $current_script = $procedure->name;
                        $script_table[$current_slot] = $current_script;
                    } else {
                        $current_script = $script_table[$current_slot];
                    }
                }

                $procedure->setScriptName($current_script);
                $procedure->setScriptId($current_slot);

                $current_slot_fill ++;
                if ($current_slot_fill >= $slot_size) {
                    // while (isset($script_table[$current_slot])) {
                    $current_slot++;
                    // }
                    $current_slot_fill = 0;
                }
            }
        }

        // const, vars output.
        $consts = [];
        $vars = [];
        foreach($ircore->items as $item) {
            if ($item instanceof AstRoot) continue;

            if ($item->isConst()) {
                $consts[] = AstDecoder::decodeConst($item);
            }
            else if ($item->isVar()) {
                $vars[] = AstDecoder::decodeVar($item);
            }
        }

        $script_name_map = [];
        if (!empty($consts)) {
            $const = new ScriptSnippet('const', true);
            $const->tag = 'block';
            $const->nodes = $consts;

            $scriptName = str_u2j($script_table[0]);
            $calllback(0, $scriptName, $const);
            $script_name_map[0] = $scriptName;
        }

        if (!empty($vars)) {
            $var = new ScriptSnippet('var', true);
            $var->tag = 'block';
            $var->nodes = $vars;

            $scriptName = str_u2j($script_table[0]);
            $calllback(0, $scriptName, $var);
            $script_name_map[0] = $scriptName;
        }

        // cycle through ir's item_procedure.
        foreach($ircore->item_procedures as $procedure) {
            if ($debugger) {
                $debugger->onIteratingProcedure($procedure, $should_read);
                if (!$should_read)
                    continue;
            }

            $final = AstRoot::walk_procedure($procedure, function($pnode, AstNode $node, $results) {
                if ($pnode == null) return $results;

                if ($results == null) {
                    // leaf nodes.
                    // print 'c:'.$node->type2.PHP_EOL;
                    return AstDecoder::decodeLeafNode($pnode, $node);
                }
                else {
                    // cross nodes.
                    // print 'p:'.$node->type2.PHP_EOL;
                    // print join(',' , $results);
                    return AstDecoder::decodeCrossNode($node, $results);
                }
            });

            $func = AstDecoder::decodeFunction($procedure, $final);

            // print static::writePlainScript(0, $func);
            $scriptId = $procedure->getScriptId();
            if ($procedure->isOnEvent()) {
                $scriptName = str_u2j($procedure->getScriptName());
            } else {
                $scriptName = '_'.str_u2j($procedure->getScriptName()).'_';
            }
            $calllback($scriptId, $scriptName, $func);
            
            if (!isset($script_name_map[$scriptId]))
                $script_name_map[$scriptId] = $scriptName;
        }

        ksort($script_name_map);
        return $script_name_map;
    }

    /**
     * @param ScriptSnippet $snippets
     */
    public static function writePlainScript($level, $snippet) : string {
        $buffer = '';
        $c = 0;
        // foreach($snippets as $snippet) {
            if (empty($snippet->tag)) {
                if (empty($snippet->nodes)) {
                    $buffer .= static::autoLineSep($c, 1);
                    $buffer .= tab($level) . $snippet->text . PHP_EOL;
                } else {
                    foreach($snippet->nodes as $node) {
                        $buffer .= static::writePlainScript($level+1, $node);
                    }
                }
            }
            else if ($snippet->tag == 'if') {
                $buffer .= static::autoLineSep($c, 2);
                $buffer .= tab($level) . 'if ' . $snippet->text . ' then' . PHP_EOL;
                if ($snippet->nodes[0]->tag == 'block') {
                    $buffer .= static::writePlainScript($level+1, $snippet->nodes[0]);
                }
                if ($snippet->nodes[1]->tag == 'block') {
                    $buffer .= tab($level) . 'else' . PHP_EOL;
                    $buffer .= static::writePlainScript($level+1, $snippet->nodes[1]);
                }
                $buffer .= tab($level) . 'end;' . PHP_EOL;
            }
            else if ($snippet->tag == 'for') {
                $buffer .= static::autoLineSep($c, 2);
                $buffer .= tab($level) . 'for ' . $snippet->text . ' do' . PHP_EOL;
                foreach($snippet->nodes as $node) {
                    $buffer .= static::writePlainScript($level+1, $node);
                }
                $buffer .= tab($level) . 'end;' . PHP_EOL;
            }
            else if ($snippet->tag == 'case') {
                $buffer .= static::autoLineSep($c, 2);
                $buffer .= tab($level) . 'case ' . $snippet->text . ' of' . PHP_EOL;
                foreach($snippet->nodes as $node) {
                    if ($node->text === '') {
                        $buffer .= tab($level+1) . 'else' . PHP_EOL;
                    } else {
                        $buffer .= tab($level+1) . $node->text . ':' . PHP_EOL;
                    }
                    $buffer .= static::writePlainScript($level+2, $node);
                    $buffer .= tab($level+1) . 'end;' . PHP_EOL;
                }
                $buffer .= tab($level) . 'end;' . PHP_EOL;
            }
            else if ($snippet->tag == 'block') {
                if ($snippet->text !== '') {
                    $buffer .= tab($level) . $snippet->text . PHP_EOL;
                }
                foreach($snippet->nodes as $node) {
                    $buffer .= static::writePlainScript($level+1, $node);
                }
            }
            else if ($snippet->tag == 'body') {
                $buffer .= tab($level) . $snippet->text . PHP_EOL;
                foreach($snippet->nodes as $node) {
                    $buffer .= static::writePlainScript($level+1, $node);
                }
                $buffer .= tab($level) . 'end;' . PHP_EOL;
                $buffer .= tab($level) . PHP_EOL;
            }

        // }
        return $buffer;
    }

    protected static function autoLineSep(&$c, $l) : string {
        if ($c == 0) {
            $c = $l; return '';
        }
        if ($c != $l) {
            $c = $l; return PHP_EOL;
        } else {
            if ($l == 2) {
                return PHP_EOL;
            }
        }
        return '';
    }
}
