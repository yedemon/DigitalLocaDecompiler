<?php

declare(strict_types=1);

namespace Digital\IRCore;

use Digital\Ast\AstRoot;
use Digital\Ast\AstNode;

/** 
 * ir core，only store data..
 */
Class IRCore {
    /**
     * @var AstNode[]
     */
    public $items;   // all items

    /** @var AstRoot[] */
    public $item_procedures;

}