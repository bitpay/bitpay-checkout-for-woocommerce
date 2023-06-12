<?php

namespace BitPayKeyUtils\Storage;

use BitPayKeyUtils\KeyHelper\KeyInterface;
use BitPayKeyUtils\Util\Util;
use Exception;

/**
 */
class EncryptedFilesystemStorage implements StorageInterface
{
    /**
     * Initialization Vector
     */
    const IV = '0000000000000000';
    /**
     * @var string
     */
    const METHOD = 'AES-128-CBC';
    /**
     * @var int
     */
    const OPENSSL_RAW_DATA = 1;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $unencoded_password;

    /**
     * @param string $password
     */
    public function __construct($password)
    {
        //to make this an non-breaking api change,
        //I will have to keep both versions of the password
        $this->password = base64_encode($password);
        $this->unencoded_password = $password;
    }

    /**
     * @inheritdoc
     */
    public function persist(KeyInterface $key)
    {
        $path = $key->getId();
        $data = serialize($key);
        $encoded = bin2hex(openssl_encrypt(
            $data,
            self::METHOD,
            $this->password,
            1,
            self::IV
        ));

        file_put_contents($path, $encoded);
    }

    /**
     * @inheritdoc
     * @throws Exception
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

        $encoded = file_get_contents($id);
        $decoded = openssl_decrypt(Util::binConv($encoded), self::METHOD, $this->password, 1, self::IV);

        if (false === $decoded) {
            $decoded = openssl_decrypt(Util::binConv($encoded), self::METHOD, $this->unencoded_password, 1, self::IV);
        }

        if (false === $decoded) {
            throw new Exception('Could not decode key');
        }

        return unserialize($decoded);
    }
}
