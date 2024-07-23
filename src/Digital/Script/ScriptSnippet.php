<?php

declare(strict_types=1);

namespace Digital\Script;

/**
 * code snippet.
 */
class ScriptSnippet {
    /**
     * current snippet is already a line.
     */
    public bool $line;

    /**
     * cur text for composing.
     */
    public string $text;

    /**
     * childern..
     * @var ScriptSnippet[]
     */
    public $nodes;

    public string $tag = '';
    /**
     * @var int 
     */
    public int $valtype = VT_UNK;

    public function __construct($text, $line = false) {
        $this->text = $text;
        $this->nodes = [];
        $this->line = $line;
    }

    public function __toString() {
        return $this->text;
    }

    /**
     * single value, literal, function ret, indexer, warpped. 
     *  no need to warp with ()
     */
    public function operant() {
        if ($this->tag == '<V>' || $this->tag == '<L>' || $this->tag == '<W>'
             || $this->tag == '<P>' || $this->tag == '<I>' || $this->tag == '<S>') {
            return $this->text;
        }

        return '(' . $this->text . ')';
    }

    public function append($text) : ScriptSnippet {
        $this->text .= $text;
        return $this;
    }
}
