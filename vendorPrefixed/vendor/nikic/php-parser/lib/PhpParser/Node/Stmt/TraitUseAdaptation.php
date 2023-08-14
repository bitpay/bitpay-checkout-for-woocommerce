<?php

declare (strict_types=1);
namespace BitPayVendor\PhpParser\Node\Stmt;

use BitPayVendor\PhpParser\Node;
abstract class TraitUseAdaptation extends Node\Stmt
{
    /** @var Node\Name|null Trait name */
    public $trait;
    /** @var Node\Identifier Method name */
    public $method;
}
