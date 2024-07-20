<?php

declare(strict_types=1);

namespace Digital\Ast;

use Digital\PCode\PCodeReader;

class AstNode {
    /** @var AstNode[] $nodes */
    public $nodes;

    public string $type2;

    private array $_attris;

    public function __construct() {
        $this->nodes = [];
        $this->_attris = [];
    }

    public function __set(string $attr, $value) 
    {
        $this->$attr = $value;
        $this->_attris[] = $attr;
    }

    public function __get(string $attr) 
    {
        return $this->$attr;
    }
    
    public function getAttris() {
        return $this->_attris;
    }

    public function isConst() : bool {
        return $this->cov == TYPE_CONST;
    }

    public function isVar() : bool {
        return $this->cov == TYPE_VAR;
    }

    public function isArray() : bool {
        return $this->type2 == AstFactory::Array;
    }

    public function isString() : bool {
        return $this->_valType == PCodeReader::VAL_STRING; //ItemReader::is_string($this->valType);
    }

    public function getValTypeStr() : string {
        if ($this->type2 == AstFactory::Value
            || $this->type2 == AstFactory::Array
            || $this->type2 == AstFactory::Literal
            || $this->type2 == AstFactory::Proper)
            return PCodeReader::getValueTypeStr($this->_valType);
        return 'N/A';
    }

    public function getLiteralValue() {
        if ($this->type2 == AstFactory::Literal) {
            return $this->val;
        }
        return null;
    }

    /**
     * _valType into VT
     * @return int
     */
    public function getVt() : int {
        if ($this->type2 == AstFactory::Value
            || $this->type2 == AstFactory::Array
            || $this->type2 == AstFactory::Literal
            || $this->type2 == AstFactory::Proper)
            return PCodeReader::valTypeToVT($this->_valType);
        return VT_UNK;
    }

    public function skipTransNode() : AstNode {
        if ($this->type2 == AstFactory::Trans) {
            $tcode = $this->tcode;
            $env = $this->env;
            $ret = $this->nodes[0]->skipTransNode();
            $ret->tcode = $tcode;
            $ret->env = $env;
            return $ret;
        }
        return $this;
    }
}
