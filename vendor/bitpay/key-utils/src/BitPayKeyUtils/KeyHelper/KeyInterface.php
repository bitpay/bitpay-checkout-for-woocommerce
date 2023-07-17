<?php

namespace BitPayKeyUtils\KeyHelper;

use Serializable;

/**
 * @package Bitcore
 */
interface KeyInterface extends Serializable
{
    /**
     * Generates a new key
     */
    public function generate();

    /**
     * @return boolean
     */
    public function isValid();
}
