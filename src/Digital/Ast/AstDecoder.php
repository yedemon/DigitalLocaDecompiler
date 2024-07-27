<?php

declare(strict_types=1);

namespace Digital\Ast;
use Digital\PCode\PCodeReader;
use Digital\PCode\Reader\EvalSystem;
use Digital\Script\ScriptSnippet;
use Exception;

/**
 * helps to turn AstNode into Script Snippets
 */
Class AstDecoder {
    
    private static function formatDouble($number) : string {
        // Check if the number is a float and has no fractional part
        if (is_float($number) && floor($number) == $number) {
            return number_format($number, 1, '.', '');
        }
        return strval($number);
    }

    private static function literalValByType($val, $valType) : string {
        switch($valType) {
            case PCodeReader::VAL_BOOL:
                $text = $val == 1 ? 'True' : 'False';
                break;
            case PCodeReader::VAL_INT:
                $text = strval($val);
                break;
            case PCodeReader::VAL_FLOAT:
                // $d = _pi();
                if ($val == pi()) {
                    $text = 'PI';
                } else {
                    $text = static::formatDouble($val);
                }
                break;
            case PCodeReader::VAL_STRING:
                $text = '\''. str_u2j($val).'\'';
                break;

            default:
                throw new Exception('unsupported literal type.');
        }
        return $text;
    }

    public static function decodeConst(AstNode $node, $isLocal=false) : ScriptSnippet {
        // sp:Float = 10;
        // EneBounus_List:Array[Ene_Max] of integer =(100,300,500,700,150,100000);
        if ($node->isArray()) {
            $vals = [];
            for ($i=0;$i<$node->_vals;$i++) {
                $vals[] = static::literalValByType($node->_vals[$i], $node->_valType);
            }
            $vals = implode(', ', $vals);
            $const = str_u2j($node->name) . ':Array[' . $node->size . '] of ' . $node->getValTypeStr() . ' = (' . $vals . ');';
        } else {
            $const = str_u2j($node->name) . ':' . $node->getValTypeStr() . ' = ' . static::literalValByType($node->val, $node->_valType) . ';';
        }
        
        return new ScriptSnippet($const, true);
    }

    public static function decodeVar(AstNode $node, $isLocal=false) : ScriptSnippet {
        // sp_xz:Float;
        // Ene_Pos1:Array[Ene_Count+1] of integer;
        if ($isLocal) {
            if ($node->isArray()) {
                $var = str_u2j($node->name) . ':Array[' . $node->size . '] of ' . $node->getValTypeStr() . ';';
            } else {
                $var = str_u2j($node->name) . ':' . $node->getValTypeStr() . ';';
            }
        } else {
            if ($node->isArray()) {
                $vals = [];
                for ($i=0;$i<$node->_vals;$i++) {
                    $vals[] = static::literalValByType($node->_vals[$i], $node->_valType);
                }
                $vals = implode(', ', $vals);
                $var = str_u2j($node->name) . ':Array[' . $node->size . '] of ' . $node->getValTypeStr() . ' = (' . $vals . ');';
            } else {
                $var = str_u2j($node->name) . ':' . $node->getValTypeStr() . ' = ' . static::literalValByType($node->val, $node->_valType) . ';';
            }
        }

        return new ScriptSnippet($var, true);
    }
    
    /**
     * @return ScriptSnippet[]
     */
    public static function decodeLocalItems(AstRoot $root, $corv) {
        $snippets = [];

        $localCount = $root->getLocalCount();
        for ( $i = 0; $i < $localCount; $i++ ) {
            $local = $root->getLocalItem($i);

            if ( $corv == TYPE_CONST ) {
                if ( $local->isConst() ) {
                    // sp:Float = 10;
                    // $const = $local->name . ':' . $local->getValTypeStr() . ' = ' . $local->val . ';';
                    // $snippet = new ScriptSnippet($const, true);
                    $snippet = static::decodeConst($local, true);
                    $snippets[] = $snippet;
                }
            }
            else if ($corv == TYPE_VAR) {
                if ( $local->isVar() ) {
                    // sp_xz:Float;
                    // $var = $local->name . ':' . $local->getValTypeStr() . ';';
                    // $snippet = new ScriptSnippet($var, true);
                    $snippet = static::decodeVar($local, true);
                    $snippets[] = $snippet;
                }
            }
        }

        return $snippets;
    }

    /**
     * @param ScriptSnippet[] $final
     */
    public static function decodeFunction(AstRoot $root, $final) : ScriptSnippet {
        // create function declear..
        if ($root->isProcedure()) {
            $funcType = 'procedure ';
            $retType = '';
            $scriptName = '';
        } else if ($root->isFunction()) {
            $funcType = 'function ';
            $retItem = $root->getReturnItem();
            $retType = ':' . $retItem->getValTypeStr();
            $scriptName = '';
        } else if ($root->isOnEvent()) {
            $funcType = 'OnEvent ';
            $retType = '';
            $scriptName = '// ' . str_u2j($root->getScriptName());
        }
        $funcName = str_u2j($root->name);

        $params = [];
        $paramCount = $root->getParamCount();
        for ( $i = 0; $i < $paramCount; $i++ ) {
            $param = $root->getParamItem($i);
            if (isset($param->isRef)) {
                $params[] = 'var ' . $param->name . ':' . $param->getValTypeStr();
            } else {
                $params[] = $param->name . ':' . $param->getValTypeStr();
            }
        }

        if ($paramCount > 0)
            $text = $funcType . $funcName . '(' . implode(';', $params) .')' . $retType . ';' . $scriptName;
        else 
            $text = $funcType . $funcName . $retType . ';' . $scriptName;

        $snippet = new ScriptSnippet($text, true);
        $snippet->tag = 'body';

        $consts = AstDecoder::decodeLocalItems($root, TYPE_CONST);
        if (!empty($consts)) {
            $const = new ScriptSnippet('const', true);
            $const->tag = 'block';
            $const->nodes = $consts;
            $snippet->nodes[] = $const;
        }

        $vars = AstDecoder::decodeLocalItems($root, TYPE_VAR);
        if (!empty($vars)) {
            $var = new ScriptSnippet('var', true);
            $var->tag = 'block';
            $var->nodes = $vars;
            $snippet->nodes[] = $var;
        }

        if (!empty($final)) {
            $begin = '';
            if (!empty($consts) || !empty($vars)) 
                $begin = 'begin';
            $body = new ScriptSnippet($begin, true);
            $body->tag = 'block';
            $body->nodes = $final;
            $snippet->nodes[] = $body;
        }

        return $snippet;
    }

    /**
     * should be these..
     * c:array
     * c:casecond
     * c:empty
     * c:literal
     * c:object
     * c:value
     */
    public static function decodeLeafNode(AstNode $pnode, AstNode $node): ScriptSnippet {
        $snippet = null;
        switch ($node->type2) {
            case AstFactory::Array:
                $snippet = new ScriptSnippet(str_u2j($node->name));
                $snippet->tag = '<A>';
                $snippet->valtype = $node->getVt();
                break;

            case AstFactory::EMPTY:
                $snippet = new ScriptSnippet('');
                $snippet->tag = '<E>';
                break;

            case AstFactory::Literal:
                // $return = strval($node->val);
                $text = '';
                if (!empty($node->alias)) {
                    $text = str_u2j($node->alias);
                } else {
                    /*switch($node->_valType) {
                        case PCodeReader::VAL_BOOL:
                            $text = $node->val == 1 ? 'True' : 'False';
                            break;
                        case PCodeReader::VAL_INT:
                            $text = strval($node->val);
                            break;
                        case PCodeReader::VAL_FLOAT:
                            // $d = _pi();
                            if ($node->val == pi()) {
                                $text = 'PI';
                            } else {
                                $text = static::formatDouble($node->val);
                            }
                            break;
                        case PCodeReader::VAL_STRING:
                            $text = '\''. str_u2j($node->val).'\'';
                            break;

                        default:
                            throw new Exception('unsupported literal type.');
                    }*/
                    $text = static::literalValByType($node->val, $node->_valType);
                }
                $snippet = new ScriptSnippet($text);
                $snippet->tag = '<L>';
                $snippet->valtype = $node->getVt();
                break;

            case AstFactory::Object:
                $snippet = new ScriptSnippet(str_u2j($node->name));
                $snippet->tag = '<O>';
                break;

            case AstFactory::Value:
                $snippet = new ScriptSnippet(str_u2j($node->name));
                $snippet->tag = '<V>';
                $snippet->valtype = $node->getVt();
                break;

            case AstFactory::CASECOND:
                $ranges = $node->_ranges;
                $range_arr = [];
                foreach ($ranges as $range) {
                    if (is_array($range)) {
                        $range_arr[] = $range[0] . '..' . $range[1];
                    }else {
                        $range_arr[] = $range;
                    }
                }
                $snippet = new ScriptSnippet(join(', ', $range_arr));
                break;
            
            default:
                throw new Exception('unexpected leaf type.');
        }
        return $snippet;
    }

    /**
     * should be these..
     * p:assign
     * p:binary
     * p:block
     * p:call
     * p:case
     * p:casebranch
     * p:for
     * p:forcond
     * p:if
     * p:indexer
     * p:proper
     * p:subobj
     * p:syscall
     * p:unary
     * p:usercall
     * p:ref
     * p:val
     * @param ScriptSnippet[] $results
     */
    public static function decodeCrossNode(AstNode $node, $results): ScriptSnippet {
        $snippet = '';
        switch ($node->type2) {
            case AstFactory::Assign:
                $snippet = static::snippetAssign($node, $results);
                break;
            case AstFactory::Proper:
                $propname = empty($node->name) ? $node->prop : $node->name;
                $snippet = $results[0]->append('.' . $propname);
                $snippet->tag = '<P>';
                $snippet->valtype = $node->getVt();
                break;
            case AstFactory::Indexer:
                // A[B:C] or A[B]
                $snippet = static::snippetIndexer($node, $results);
                break;
            case AstFactory::Bindexer:
                $snippet = static::snippetBindexer($node, $results);
                break;
            case AstFactory::ScoreTrack:
                $snippet = static::snippetScoreTrack($node, $results);
                break;

            case AstFactory::Subobject:
                $subname = empty($node->name) ? $node->pcode : $node->name;
                $snippet = $results[0]->append('.' . $subname);
                break;

            case AstFactory::Unary:
                $snippet = static::decodeUnary($node, $results);
                break;
            case AstFactory::Binary:
                $snippet = static::decodeBinary($node, $results);
                break;

            case AstFactory::BLOCK:
                // if (count($results) == 1) {
                //     return $results[0];
                // }
                $snippet = new ScriptSnippet('', true);
                $snippet->tag = 'block';
                $snippet->nodes = $results;
                break;

            case AstFactory::UserCall:
                $snippet = static::snippetCalls($node, $results);
                break;
            case AstFactory::ParamRef:
                $snippet = $results[0];
                $snippet->tag = 'ref';
                break;
            case AstFactory::ParamVal:
                $snippet = $results[0];
                $snippet->tag = 'val';
                break;

            case AstFactory::Call:
                $snippet = static::snippetObjectCalls($node, $results);
                break;
            case AstFactory::SysCall:
                $snippet = static::snippetCalls($node, $results);
                break;
            
            case AstFactory::IF:
                $snippet = static::snippetIf($results);
                break;

            case AstFactory::FOR:
                $snippet = static::snippetFor($results);
                break;

            case AstFactory::FORCOND:
                $snippet = static::snippetForCond($results);
                break;

            case AstFactory::CASE:
                $snippet = static::snippetCase($results);
                break;

            case AstFactory::CASEBRANCH:
                $snippet = static::snippetCaseSub($results);
                break;

            default:
                throw new Exception('unexpected cross type.');
        }

        return $snippet;
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetAssign($node, $s) : ScriptSnippet {
        if (count($s) == 2) {
            // print $s[0]->valtype .' <- '. $s[1]->valtype . PHP_EOL;
            // using avaliable valtype to convert type
            $right = $s[1]->text;
            if ($s[0]->valtype != VT_UNK && $s[1]->valtype != VT_UNK) {
                if ($s[0]->valtype == VT_STRING) {
                    if ($s[1]->valtype == VT_BOOL) {
                        $right = 'BoolToStr(' . $right . ')';
                    } else if ($s[1]->valtype == VT_INT) {
                        $right = 'IntToStr(' . $right . ')';
                    } else if ($s[1]->valtype == VT_FLOAT) {
                        $right = 'FloatToStr(' . $right . ')';
                    }
                }
                if ($s[1]->valtype == VT_STRING) {
                    if ($s[0]->valtype == VT_BOOL) {
                        // $right = 'BoolToStr(' . $right . ')';
                    } else if ($s[0]->valtype == VT_INT) {
                        $right = 'StrToInt(' . $right . ')';
                    } else if ($s[0]->valtype == VT_FLOAT) {
                        $right = 'StrToFloat(' . $right . ')';
                    }
                }
            }

            // TODO: what? i forgot what this is..
            // if ( isset($node->nodes[0]->name) && strpos($node->nodes[0]->name, 'param') !==false )
            //     print( 'vt>' . $node->nodes[0]->name . ' is ' . $node->nodes[0]->_valType . PHP_EOL);

            return new ScriptSnippet($s[0]->text .' = '. $right . ';', true);
        } else {
            throw new Exception('Assign takes 2 snippets..');
        }
    }
    
    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetIndexer($node, $s) : ScriptSnippet {
        if (count($s) == 2) {
            // $index = empty($node->alias) ? $s[1]->text : $node->alias;
            $snippet = new ScriptSnippet($s[0]->text .'['. $s[1]->text . ']');
            if (isset($node->_valType)) {
                $snippet->valtype = $node->getVt();
            } else {
                $snippet->valtype = VT_UNK;
            }
            $snippet->tag = '<I>';
            return $snippet;
        } else {
            throw new Exception('Indexer takes 2 snippets..');
        }
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetBindexer($node, $s) : ScriptSnippet {
        if (count($s) == 3) {
            // $index1 = empty($node->alias) ? $s[1]->text : $node->alias;
            // $index2 = empty($node->alias2) ? $s[2]->text : $node->alias2;
            $snippet = new ScriptSnippet($s[0]->text .'['. $s[1]->text . ':' . $s[2]->text .']');
            if (isset($node->_valType)) {
                $snippet->valtype = $node->getVt();
            } else {
                $snippet->valtype = VT_UNK;
            }
            $snippet->tag = '<I>';
            return $snippet;
        } else {
            throw new Exception('Bindexer takes 3 snippets..');
        }
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetScoreTrack($node, $s) : ScriptSnippet {
        if (count($s) == 2) {
            if ($s[0]->text === '0') {
                $text = $s[1]->text;
            } else {         
                $text = $s[0]->text .':'. $s[1]->text;
            }
            // $snippet = new ScriptSnippet($s[0]->text . ':' . $s[1]->text);
            $snippet = new ScriptSnippet($text);
            $snippet->valtype = VT_UNK;
            $snippet->tag = '<S>';
            return $snippet;
        } else {
            throw new Exception('ScoreTrack takes 2 snippets..');
        }
    }
    
    /**
     * @param ScriptSnippet[] $s
     */
    private static function snippetFor($s) : ScriptSnippet {
        if (count($s) == 2) {
            $snippet = new ScriptSnippet($s[0]->text);
            $snippet->nodes = $s[1]->nodes;

            $snippet->tag = 'for';

            return $snippet;
        } else {
            throw new Exception('For must take 2 snippets..');
        }
    }

    /**
     * @param ScriptSnippet[] $s
     */
    private static function snippetForCond($s) : ScriptSnippet {
        if (count($s) == 4) {
            $condtext = $s[0]->text .'='. $s[1] . ' To ' . $s[2] .' by ' . $s[3];
            $snippet = new ScriptSnippet($condtext);
            return $snippet;
        } else {
            throw new Exception('For cond must take 4 snippets..');
        }
    }

    /**
     * @param ScriptSnippet[] $s
     */
    private static function snippetIf($s) : ScriptSnippet {
        if (count($s) == 3) {
            $snippet = new ScriptSnippet($s[0]->text, true);
            $snippet->nodes[] = $s[1];
            $snippet->nodes[] = $s[2];

            $snippet->tag = 'if';

            return $snippet;
        } else {
            throw new Exception('IF must take 3 snippets..');
        }
    }

    private static function snippetCase($s) : ScriptSnippet {
        $snippet = new ScriptSnippet($s[0]->text, true);
        $snippet->nodes = array_slice($s, 1);

        $snippet->tag = 'case';

        return $snippet;
    }

    private static function snippetCaseSub($s) : ScriptSnippet {
        if (count($s) == 2) {
            $snippet = new ScriptSnippet($s[0]->text, true);
            // $snippet->nodes[] = $s[1];
            // if ($s[1]->tag == 'block') {
            //     $snippet->nodes = $s[1]->nodes;
            // } else {
            $snippet->nodes = $s[1]->nodes;
            // }

            return $snippet;
        } else {
            throw new Exception('Case branch must take 2 snippets..');
        }
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetObjectCalls($node, $s) : ScriptSnippet {
        // get container from $s[0]...
        $container = $s[0];
        $func = $container->text . '.' . $node->name;

        $s1 = $s[1];
        $params = [];
        foreach ($s1->nodes as $pnode) {
            $params[] = $pnode->text;
        }

        if (empty($params)) {
            // return new ScriptSnippet($func, $line);
            $text = $func;
        } else {
            $text = $func. '('. join(', ', $params) . ')';
        }
        
        return static::snippet_calls_final($node, $text);
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetCalls($node, $s) : ScriptSnippet {
        $func = str_u2j($node->name);
        if ($func == 'SeekFrameEx') {
            // SeekFrame special handle
            return static::snippetSeekFrameEx($node, $s);
        }
        // else if ($func == 'GetCrossPoint') {
        //     return static::snippetGetCrossPointEx($node, $s, false);
        // }
        // else if ($func == 'GetCrossPointEx') {
        //     return static::snippetGetCrossPointEx($node, $s, true);
        // }
        // else if ($func == 'CollisionCheck') {
        //     return static::snippetCollisionCheckEx($node, $s, false);
        // }
        // else if ($func == 'CollisionCheckEx') {
        //     return static::snippetCollisionCheckEx($node, $s, true);
        // }
        else if ($func == 'BreakLoopEx') {
            return static::snippetBreakLoopEx($node, $s);
        }

        // $s has a subelement block as params.
        $s0 = $s[0];
        $params = [];
        foreach ($s0->nodes as $pnode) {
            $params[] = $pnode->text;
        }
        
        if (empty($params)) {
            // return new ScriptSnippet($func, $line);
            $text = $func;
        } else {
            $text = $func. '('. join(', ', $params) . ')';
        }

        return static::snippet_calls_final($node, $text);
    }

    private static function callisline($node) {
        // those comes after X46 (X47) won't be an intact line.
        if (isset($node->env)) {
            if ($node->env == AstFactory::TRANS_EVAL_SYSTEM) {
                if ($node->tcode == EvalSystem::X46
                    || $node->tcode == EvalSystem::X47) {
                        return false;
                    }
            }
        }
        return true;
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetSeekFrameEx($node, $s) : ScriptSnippet {
        $s0 = $s[0];
        $param0 = $s0->nodes[0]->text;
        $param1 = $s0->nodes[1]->text;
        $param2 = $s0->nodes[2]->text;
        if ($param2 == 'True') {
            // SeekFrame..
            // if the first param is '0', then it must be ignored or written as 'root:'
            // here i choose to ignore.
            if ($param0 === '0') {
                $text = 'SeekFrame('. $param1 .')';
            } else {         
                $text = 'SeekFrame('. $param0 .':'. $param1 .')';
            }
        }
        else {
            if ($param0 === '0') {
                $text = 'SeekFrameEx('. $param1 .', '. $param2 .')';
            } else {
                $text = 'SeekFrameEx('. $param0 .':'. $param1 .', '. $param2 .')';
            }
        }

        return static::snippet_calls_final($node, $text);
    }

    /**
     * @deprecated 
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetGetCrossPointEx($node, $s, $isEx) : ScriptSnippet {
        $s0 = $s[0];
        $param0 = $s0->nodes[0]->text;//px
        $param1 = $s0->nodes[1]->text;//py
        $param2 = $s0->nodes[2]->text;//pz
        $param3 = $s0->nodes[3]->text;//nx
        $param4 = $s0->nodes[4]->text;//ny
        $param5 = $s0->nodes[5]->text;//nz
        $param6 = $s0->nodes[6]->text;//dist
        $param7 = $s0->nodes[7]->text;//score
        $param8 = $s0->nodes[8]->text;//track
        $param9 = $s0->nodes[9]->text;//Flag
        if ($isEx) {
            $param10 = $s0->nodes[10]->text;//LowerGroup
            $param11 = $s0->nodes[11]->text;//UpperGroup

            if ($param7 === '0') {
                $text = 'GetCrossPointEx('. $param0 .', '. $param1 .', '
                                            . $param2 .', '. $param3 .', '
                                            . $param4 .', '. $param5 .', '. $param6 .', ' 
                                            . $param8 .', '. $param9 .', '
                                            . $param10 .', '. $param11 .')';
            } else {
                $text = 'GetCrossPointEx('. $param0 .', '. $param1 .', '
                                            . $param2 .', '. $param3 .', '
                                            . $param4 .', '. $param5 .', '. $param6 .', ' 
                                            . $param7 .':'. $param8 .', '. $param9 .', '
                                            . $param10 .', '. $param11 .')';
            }
        }
        else {
            if ($param7 === '0') {
                $text = 'GetCrossPoint('. $param0 .', '. $param1 .', '
                                            . $param2 .', '. $param3 .', '
                                            . $param4 .', '. $param5 .', '. $param6 .', ' 
                                            . $param8 .', '. $param9 .')';
            } else {
                $text = 'GetCrossPoint('. $param0 .', '. $param1 .', '
                                            . $param2 .', '. $param3 .', '
                                            . $param4 .', '. $param5 .', '. $param6 .', ' 
                                            . $param7 .':'. $param8 .', '. $param9 .')';
            }
        }

        return static::snippet_calls_final($node, $text);
    }

    /**
     * @deprecated 
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetCollisionCheckEx($node, $s, $isEx) : ScriptSnippet {
        $s0 = $s[0];
        $param0 = $s0->nodes[0]->text;//ScoreA
        $param1 = $s0->nodes[1]->text;//TrackA
        $param2 = $s0->nodes[2]->text;//ScoreB
        $param3 = $s0->nodes[3]->text;//TrackB

        if ($param0 === '0') {
            $p1 = $param1;
        } else {
            $p1 = $param0 .':'. $param1;
        }
        if ($param2 === '0') {
            $p2 = $param3;
        } else {
            $p2 = $param2 .':'. $param3;
        }
        if (!$isEx) {
            $text = 'CollisionCheck('. $p1 .', '. $p2 .')';
        } else {
            $param4 = $s0->nodes[4]->text;//Type
            $text = 'CollisionCheckEx('. $p1 .', '. $p2 .', '. $param4 .')';
        }
        
        return static::snippet_calls_final($node, $text);
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function snippetBreakLoopEx($node, $s) : ScriptSnippet {
        $s0 = $s[0];
        $param0 = $s0->nodes[0]->text;
        $param1 = $s0->nodes[1]->text;
        if ($param1 == 'True') {
            // BreakLoop..
            if ($param0 === '0') {
                $text = 'BreakLoop';
            } else {         
                $text = 'BreakLoop('. $param0 .')';
            }
        }
        else {
            if ($param0 === '0') {
                $text = 'BreakLoopEx('. $param1 .')';
            } else {
                $text = 'BreakLoopEx('. $param0 .', '. $param1 .')';
            }
        }

        return static::snippet_calls_final($node, $text);
    }

    private static function snippet_calls_final($node, $text) : ScriptSnippet {
        $line = static::callisline($node);
        if ($line) {
            $text .= ';';
        }
        $snippet = new ScriptSnippet($text, $line);
        if (!$line) {
            $snippet->tag = '<W>';
        }

        return $snippet;
    }

    /**
     * @param AstNode $node
     * @param ScriptSnippet[] $s
     */
    private static function decodeUnary($node, $s) : ScriptSnippet {
        if (count($s) == 1) {
            $retext = '';
            $valType = VT_UNK;
            switch($node->pcode) {
                case EvalSystem::NEG:
                    $retext = '-' . $s[0];
                    $valType = $s[0]->valtype;
                    break;
                case EvalSystem::NOT:
                    $retext = 'not('. $s[0].')';
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::ABS:
                    $retext = 'Abs('. $s[0].')';
                    $valType = VT_INT;
                    break;
                case EvalSystem::ABSF:
                    $retext = 'AbsF('. $s[0].')';
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::COS:
                    $retext = 'Cos('. $s[0].')';
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::SIN:
                    $retext = 'Sin('. $s[0].')';
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::SQRT:
                    $retext = 'Sqrt('. $s[0].')';
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::TRUNC:
                    $retext = 'Trunc('. $s[0].')';
                    $valType = VT_INT;
                    break;
                case EvalSystem::FloatToStr:
                    $retext = 'FloatToStr('. $s[0].')';
                    $valType = VT_STRING;
                    break;
                case EvalSystem::StrLength:
                    $retext = 'StrLength('. $s[0] .')';
                    $valType = VT_INT;
                    break;
            }
            $snippet = new ScriptSnippet($retext);
            $snippet->tag = '<W>';
            $snippet->valtype = $valType;
            return $snippet;
        } else {
            throw new Exception('Unary over 1 items..');
        }
    }

    /**
     * @param ScriptSnippet[] $s
     */
    private static function decodeBinary($node, $s) : ScriptSnippet {
        if (count($s) == 2) {
            $retext = '';
            $nowrap = false;
            $valType = VT_UNK;
            switch($node->pcode) {
                case EvalSystem::ADD_VAR:
                    $retext = $s[0]->operant() . ' + ' . $s[1]->operant();
                    $valType = $s[0]->valtype;
                    break;
                case EvalSystem::SUB_VAR:
                    $retext = $s[0]->operant() . ' - ' . $s[1]->operant();
                    $valType = $s[0]->valtype;
                    break;
                case EvalSystem::DIV_FLOAT:
                    $retext = $s[0]->operant() . ' / ' . $s[1]->operant();
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::AND_INT:
                    $retext = $s[0]->operant() . ' and ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;
                case EvalSystem::OR_INT:
                    $retext = $s[0]->operant() . ' or ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;
                case EvalSystem::NEQ:
                    $retext = $s[0]->operant() . ' <> ' . $s[1]->operant();
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::EQ:
                    $retext = $s[0]->operant() . ' = ' . $s[1]->operant();
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::LT:
                    $retext = $s[0]->operant() . ' < ' . $s[1]->operant();
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::GT:
                    $retext = $s[0]->operant() . ' > ' . $s[1]->operant();
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::LTE:
                    $retext = $s[0]->operant() . ' <= ' . $s[1]->operant();
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::GTE:
                    $retext = $s[0]->operant() . ' >= ' . $s[1]->operant();
                    $valType = VT_BOOL;
                    break;
                case EvalSystem::SUB_INT:
                    $retext = $s[0]->operant() . ' - ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;
                case EvalSystem::SUB_FLOAT:
                    $retext = $s[0]->operant() . ' - ' . $s[1]->operant();
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::ARCTAN2:
                    $retext = 'ArcTan2('.$s[0] . ',' . $s[1].')';
                    $valType = VT_FLOAT;
                    $nowrap = true;
                    break;
                case EvalSystem::MUL_VAR:
                    $retext = $s[0]->operant() . ' * ' . $s[1]->operant();
                    $valType = $s[0]->valtype;
                    break;
                case EvalSystem::MUL_INT:
                    $retext = $s[0]->operant() . ' * ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;
                case EvalSystem::MUL_FLOAT:
                    $retext = $s[0]->operant() . ' * ' . $s[1]->operant();
                    $valType = VT_FLOAT;
                    break;
                case EvalSystem::ADD_INT:
                    $retext = $s[0]->operant() . ' + ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;
                case EvalSystem::ADD_FLOAT:
                    $retext = $s[0]->operant() . ' + ' . $s[1]->operant();
                    $valType = VT_FLOAT;
                    break;

                case EvalSystem::StrCopyRight:
                    $retext = 'StrCopyRight('.$s[0] . ',' . $s[1].')';
                    $valType = VT_STRING;
                    $nowrap = true;
                    break;
                case EvalSystem::StrDeleteRight:
                    $retext = 'StrDeleteRight('.$s[0] . ',' . $s[1].')';
                    $valType = VT_STRING;
                    $nowrap = true;
                    break;

                case EvalSystem::X24:
                    $retext = $s[0]->operant() . ' rol ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;
                case EvalSystem::X25:
                    $retext = $s[0]->operant() . ' ror ' . $s[1]->operant();
                    $valType = VT_INT;
                    break;

                case EvalSystem::ADD_STRING:
                    $retext = static::toStrWrap($s[0]) . ' + ' . static::toStrWrap($s[1]);
                    $valType = VT_STRING;
                    break;
            }
            $snippet = new ScriptSnippet($retext);
            if ($nowrap)
                $snippet->tag = '<W>';
            $snippet->valtype = $valType;
            return $snippet;
        } else {
            throw new Exception('Binary over 2 items..');
        }
    }

    public static function castNumberWrap($castCateId, $name) : string {
        $ret = '';
        switch($castCateId) {
            case CAST_MODEL:
                $ret = 'CastNumber(MODEL:' . $name . ')';
                break;
            case CAST_TEXTURE:
                $ret = 'CastNumber(TEXTURE:' . $name . ')';
                break;
            case CAST_BITMAP:
                $ret = 'CastNumber(BITMAP:' . $name . ')';
                break;
            case CAST_TEXT:
                $ret = 'CastNumber(TEXT:' . $name . ')';
                break;
            case CAST_WAVE:
                $ret = 'CastNumber(WAVE:' . $name . ')';
                break;
            case CAST_MIDI:
                $ret = 'CastNumber(MIDI:' . $name . ')';
                break;
            case CAST_CAMERA:
                $ret = 'CastNumber(CAMERA:' . $name . ')';
                break;
            case CAST_LIGHT:
                $ret = 'CastNumber(LIGHT:' . $name . ')';
                break;
            case CAST_SOUND3D:
                $ret = 'CastNumber(SOUND3D:' . $name . ')';
                break;
            case CAST_EAR:
                $ret = 'CastNumber(EAR:' . $name . ')';
                break;
        }
        return $ret;
    }

    private static function toStrWrap(ScriptSnippet $snippet) : string {
        if (!isset($snippet->valtype)) {
            return $snippet->operant();
        }
        if ($snippet->valtype == VT_BOOL) {
            return 'BoolToStr('. $snippet->operant() .')';
        } else if ($snippet->valtype == VT_INT) {
            return 'IntToStr('. $snippet->operant() .')';
        } else if ($snippet->valtype == VT_FLOAT) {
            return 'FloatToStr(' . $snippet->operant() . ')';
        }

        return $snippet->operant();
    }
}
