<?php

namespace BitPayKeyUtils\Util;

use Serializable;

/**
 * @package Bitcore
 */
interface PointInterface extends Serializable
{
    /**
     * Infinity constant
     *
     * @var string
     */
    const INFINITY = 'inf';

    /**
     * @return string
     */
    public function getX();

    /**
     * @return string
     */
    public function getY();

    /**
     * @return boolean
     */
    public function isInfinity();
}
