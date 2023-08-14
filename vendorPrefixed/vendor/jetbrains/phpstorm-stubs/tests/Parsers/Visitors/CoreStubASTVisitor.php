<?php

declare (strict_types=1);
namespace BitPayVendor\StubTests\Parsers\Visitors;

use BitPayVendor\JetBrains\PhpStorm\Pure;
use BitPayVendor\StubTests\Model\StubsContainer;
class CoreStubASTVisitor extends ASTVisitor
{
    #[Pure]
    public function __construct(StubsContainer $stubs)
    {
        parent::__construct($stubs);
        $this->isStubCore = \true;
    }
}
