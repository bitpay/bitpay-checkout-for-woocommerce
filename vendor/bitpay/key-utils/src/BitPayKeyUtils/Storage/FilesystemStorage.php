<?php

namespace BitPayKeyUtils\Storage;

use BitPayKeyUtils\KeyHelper\KeyInterface;
use Exception;

/**
 * Used to persist keys to the filesystem
 */
class FilesystemStorage implements StorageInterface
{
    /**
     * @inheritdoc
     */
    public function persist(KeyInterface $key)
    {
        $path = $key->getId();
        file_put_contents($path, serialize($key));
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @throws Exception
     */
    public function load($id)
    {
        if (!is_file($id)) {
            throw new Exception(sprintf('Could not find "%s"', $id));
        }

        if (!is_readable($id)) {
            throw new Exception(sprintf('"%s" cannot be read, check permissions', $id));
        }

        return unserialize(file_get_contents($id));
    }
}
