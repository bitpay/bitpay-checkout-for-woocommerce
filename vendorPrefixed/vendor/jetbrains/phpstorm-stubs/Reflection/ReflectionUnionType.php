<?php

namespace BitPayVendor;

use BitPayVendor\JetBrains\PhpStorm\Pure;
/**
 * @since 8.0
 */
class ReflectionUnionType extends \ReflectionType
{
    /**
     * Get list of named types of union type
     *
     * @return ReflectionNamedType[]
     */
    #[Pure]
    public function getTypes() : array
    {
    }
}
/**
 * @since 8.0
 */
\class_alias('BitPayVendor\\ReflectionUnionType', 'ReflectionUnionType', \false);
