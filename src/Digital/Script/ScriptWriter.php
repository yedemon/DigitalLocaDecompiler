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

                AstFactory::markBindexerAlias($node, 
                function($path) use ($loca) {
                    // TODO: handle ?
                    [$name, $name2] = $loca->searchScoreTrack($path);
                    return [$name, $name2];
                });

                AstFactory::markSeekframeAlias($node,
                function($path) use ($loca) {
                    // TODO: handle ?
                    [$name, $name2] = $loca->searchScoreLabel($path);
                    return [$name, $name2];
                });

                AstFactory::markGetCrossPointAlias($node,
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

                // handle onEvent keys'

                // handle GetKeyEvent keys'
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

        foreach($ircore->item_procedures as $procedure) {
            if ($procedure->isOnEvent()) {
                $scriptId = $procedure->getScriptId();
                $scriptName = $procedure->getScriptName();

                $script_table[$scriptId] = $scriptName;

                if ($scriptId > $max_script_id) {
                    $max_script_id = $scriptId;
                }
            } else {
                $unindexed ++;
            }
        }

        $slotsAvailable = $max_script_id - count($script_table) - 1/**const+var*/ + 1;
        if (isset($script_table[0])) {
            $slotsAvailable++;
        } else {
            $script_table[0] = 'ConstVar';
        }
        // if (isset($script_table[2])) {
        //     $slotsAvailable++;
        // } else {
        //     $script_table[2] = 'Var';
        // }

        // funcs per slot
        $slot_size = ceil($unindexed / $slotsAvailable);
        if ($slot_size > 20) {
            $slot_size = 20;
        }
        $current_slot = 1;
        $current_slot_fill = 0;
        $current_script = '';

        while (isset($script_table[$current_slot])) {
            $current_slot++;
        }

        // 2. fill unnamed procedures into slots, assign scriptname
        foreach($ircore->item_procedures as $procedure) {
            if (!$procedure->isOnEvent()) {
                if ($current_slot_fill == 0) {
                    $current_script = $procedure->name;
                    $script_table[$current_slot] = $current_script;
                }

                $procedure->setScriptName($current_script);
                $procedure->setScriptId($current_slot);

                $current_slot_fill ++;
                if ($current_slot_fill >= $slot_size) {
                    while (isset($script_table[$current_slot])) {
                        $current_slot++;
                    }

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

            $calllback(0, $script_table[0], $const);
            $script_name_map[0] = $script_table[0];
        }

        if (!empty($vars)) {
            $var = new ScriptSnippet('var', true);
            $var->tag = 'block';
            $var->nodes = $vars;

            $calllback(0, $script_table[0], $var);
            $script_name_map[0] = $script_table[0];
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
