<?php

declare (strict_types=1);
namespace BitPayVendor\PhpParser\Node\Scalar\MagicConst;

use BitPayVendor\PhpParser\Node\Scalar\MagicConst;
class Line extends MagicConst
{
    public function getName() : string
    {
        return '__LINE__';
    }
    public function getType() : string
    {
        return 'Scalar_MagicConst_Line';
    }
}
