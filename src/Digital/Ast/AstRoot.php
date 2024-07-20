<?php

declare(strict_types=1);

namespace Digital\Ast;

use AstRoot\AstRootAttris;
use Digital\IRCore\IRCore;

use Digital\PCode\PCodeReader;
use DOMDocument;
use Exception;

/**
 * procedures
 */
class AstRoot extends AstNode {
    protected IRCore $ircore;
    public function __construct($base_offset, $porfore, $param_count, $item_count) {
        parent::__construct();
        // $this->ircore = $ircore;
        $this->base_offset = $base_offset;
        
        $attrs = new AstRootAttris();
        $attrs->porfore = $porfore;
        $attrs->param_count = $param_count;
        $attrs->item_count = $item_count;
        $this->attrs = $attrs;

        $this->items = [];
    }
    public function setIRCore(IRCore $ircore) {
        $this->ircore = $ircore;
    }

    /**
     * all 'inner' items，including return/param/local
     * @var AstNode[] */
    protected $items;

    protected AstRootAttris $attrs;

    // 0 is preserved. 
    // 1 for consts
    // 2 for vars
    protected $scriptId = 0;
    protected $scriptName = '';

    public function setReturnItem(AstNode $itemNode) {
        $this->items[0] = $itemNode;
    }

    public function setParamItem($paramId, AstNode $itemNode) {
        $itemId = $this->attrs->paramId2ItemId($paramId);
        $this->items[$itemId] = $itemNode;
    }

    public function setLocalItem($localId, AstNode $itemNode) {
        $itemId = $this->attrs->localId2ItemId($localId);
        $this->items[$itemId] = $itemNode;
    }

    public function getReturnItem() : AstNode {
        return $this->items[0];
    }

    public function getParamItem($paramId) : AstNode {
        $itemId = $this->attrs->paramId2ItemId($paramId);
        return $this->items[$itemId];
    }

    public function getLocalItem($localId) : AstNode {
        $itemId = $this->attrs->localId2ItemId($localId);
        return $this->items[$itemId];
    }

    public function setParamItemRef($paramId) {
        $itemId = $this->attrs->paramId2ItemId($paramId);
        $item = $this->items[$itemId];

        $item->isRef = true;
    }

    public function setParamItemValType($paramId, $valType) {
        $itemId = $this->attrs->paramId2ItemId($paramId);
        $item = $this->items[$itemId];

        // should prevent backwards..
        if ($valType == PCodeReader::VAL_UNKNOWN) {
            return;
        }
        if ($valType == PCodeReader::VAL_FLOAT) {
            $item->_valType = $valType;
        } 
        if ($item->_valType == PCodeReader::VAL_UNKNOWN) {
            if ($valType == PCodeReader::VAL_INT) {
                $item->_valType = $valType;
            } 
        }
        if ($item->_valType == PCodeReader::VAL_UNKNOWN) {
            if ($valType == PCodeReader::VAL_BOOL) {
                $item->_valType = $valType;
            } 
        }
        if ($item->_valType == PCodeReader::VAL_UNKNOWN) {
            if ($valType == PCodeReader::VAL_STRING) {
                $item->_valType = $valType;
            } 
        }
    }

    /**
     */
    protected int $base_offset;

    // protected int $param_count;
    // protected int $item_count;
    // protected int $ret_type;

    public bool $debug_print = false;

    public function getBaseOffset() : int {
        return $this->base_offset;
    }

    public function getParamCount() {
        return $this->attrs->param_count;
    }

    public function getLocalCount() {
        return $this->attrs->getLocalCount();
    }

    public function setEventType($evt_type) {
        $this->attrs->eventType = $evt_type;
    }

    public function setScriptId($script_id) {
        $this->scriptId = $script_id;
    }

    public function getScriptId() {
        return $this->scriptId;
    }

    public function setScriptName($script_name) {
        $this->scriptName = $script_name;
    }

    public function getScriptName() : string {
        return $this->scriptName;
    }

    /**
     * index -> item (value/array/procedure | global/local)
     * @return AstNode
     */
    public function getValueNode($index) : AstNode {
        if ($index < 0) {
            return $this->items[$index & 0x7FFFFFFF];
        } else {
            $gItem = $this->ircore->items[$index];
            return $gItem;
        }
    }

    public function getName() : string {
        return $this->name;
    }

    public function isProcedure() {
        return $this->attrs->porfore == TYPE_PROCEDURE;
    }

    public function isFunction() {
        return $this->attrs->porfore == TYPE_FUNCTION;
    }

    public function isOnEvent() {
        return $this->attrs->porfore == TYPE_ONEVENT;
    }

    /** temp code */
    public function printNodesXML() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        for($i = 0; $i < count($this->nodes); $i++) {
            $this->makeNodeXML($xml, null, $this->nodes[$i]);
        }

        $xml->formatOutput = true;
        echo $xml->saveXML();
    }

    private function makeNodeXML(DOMDocument $rxml, $pxml, AstNode $node) {
        // if ($node->type2 == AstFactory::Trans) {
        //     $trans_pcode = $node->tcode;
        //     $node = $node->nodes[0];
        // } 
        $node = $node->skipTransNode();

        $xml = $rxml->createElement($node->type2);

        if ($pxml == null) {
            $rxml->appendChild($xml);
        } else {
            $pxml->appendChild($xml);
        }

        $attrNames = $node->getAttris();
        foreach($attrNames as $attrName) {
            //treat _object as firstNode.
            if ($attrName == '_object') {
                // for AstRoot..
                $this->makeNodeXML($rxml, $xml, $node->_object);
                continue;
            } else if ($attrName == '_valType') {
                $attr = $rxml->createAttribute('vt');
                $attr->value = $node->getValTypeStr();
                $xml->appendChild($attr);
                continue;
            }

            //ignore _ranges
            if (strncmp($attrName, '_', 1) == 0) continue;

            $attr = $rxml->createAttribute($attrName);
            if ($attrName == "pcode" || $attrName == "tcode") {
                $attr->value = dechex($node->$attrName);
            } else {
                $attr->value = $node->$attrName;
            }
            $xml->appendChild($attr);
        }
        // if (isset($trans_pcode)) {
        //     $attr = $rxml->createAttribute('tcode');
        //     $attr->value = dechex($trans_pcode);
        //     $xml->appendChild($attr);
        // }

        if (!($node instanceof AstRoot)) {//skip astroot's children
            if ($node->nodes) {
                foreach($node->nodes as $cnode) {
                    if ($cnode instanceof AstNode) {
                        $this->makeNodeXML($rxml, $xml, $cnode);
                    } else {
                        throw new Exception('unexpected node.');
                    }
                }
            } else {
            }
        }
    }

    public static function walk_procedure($_this, $callback) {
        return static::_walk_procedure(null, $_this, $callback);
    }

    public static function _walk_procedure($pnode, AstNode $node, $callback) {
        $node = $node->skipTransNode();
        
        $results = [];
        // if (isset($node->_object) && !($node->_object instanceof AstRoot)) {
        //     $result = static::_walk_procedure($node, $node->_object, $callback);
        //     $results[] = $result;
        // }

        if (!empty($node->nodes)) {
            foreach ($node->nodes as $cnode) {
                $result = static::_walk_procedure($node, $cnode, $callback);
                $results[] = $result;
            }

            return $callback($pnode, $node, $results);
        } else {
            return $callback($pnode, $node, null);
        }

    }
}

namespace AstRoot;

/**
 * Help to store and handle param_count/ item_count
 */
class AstRootAttris {
    public string $porfore;
    
    // already sub 1 for functions
    public int $param_count;
    public int $item_count;

    public int $eventType;

    public function paramId2ItemId($paramId) {
        if ($this->porfore == TYPE_FUNCTION) {
            return $paramId + 1;
        }
        return $paramId;
    }

    public function localId2ItemId($localId) {
        if ($this->porfore == TYPE_FUNCTION) {
            return $localId + 1 + $this->param_count;
        }
        return $localId + $this->param_count;
    }

    public function getLocalCount() {
        if ($this->porfore == TYPE_FUNCTION) {
            return $this->item_count - $this->param_count - 1;
        }
        return $this->item_count - $this->param_count;
    }
}