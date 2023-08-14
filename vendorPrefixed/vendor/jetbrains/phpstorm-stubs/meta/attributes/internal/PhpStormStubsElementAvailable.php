<?php

namespace BitPayVendor\JetBrains\PhpStorm\Internal;

use Attribute;
use BitPayVendor\JetBrains\PhpStorm\Deprecated;
use BitPayVendor\JetBrains\PhpStorm\ExpectedValues;
/**
 * For PhpStorm internal use only
 * @since 8.0
 * @internal
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
class PhpStormStubsElementAvailable
{
    public function __construct(#[ExpectedValues(Deprecated::PHP_VERSIONS)] $from, #[ExpectedValues(Deprecated::PHP_VERSIONS)] $to = null)
    {
    }
}
