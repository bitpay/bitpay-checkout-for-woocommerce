<?php

namespace BitPayKeyUtils\Storage;

use BitPayKeyUtils\KeyHelper\KeyInterface;

/**
 * @package Bitcore
 */
interface StorageInterface
{
    /**
     * @param KeyInterface $key
     */
    public function persist(KeyInterface $key);

    /**
     * @param string $id
     *
     * @return KeyInterface
     */
    public function load($id);
}
