<?php

namespace BitPayKeyUtils\KeyHelper;

use BitPayKeyUtils\Math\Math;
use BitPayKeyUtils\Util\Point;
use BitPayKeyUtils\Util\Secp256k1;
use BitPayKeyUtils\Util\Util;
use Exception;

/**
 * @package Bitcore
 */
class PublicKey extends Key
{
    /**
     * @var SinKey
     */
    protected $sin;

    /**
     * @var PrivateKey
     */
    protected $privateKey;

    /**
     * @param PrivateKey
     * @return PublicKey
     */
    public static function createFromPrivateKey(PrivateKey $private)
    {
        $public = new self();
        $public->setPrivateKey($private);

        return $public;
    }

    /**
     * @param PrivateKey $privateKey
     * @return KeyInterface
     */
    public function setPrivateKey(PrivateKey $privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Returns the compressed public key value
     *
     * @return string
     */
    public function __toString()
    {
        if (is_null($this->x)) {
            return '';
        }

        if (Math::mod('0x' . $this->y, '0' . 'x02') == '1') {
            return sprintf('03%s', $this->x);
        } else {
            return sprintf('02%s', $this->x);
        }
    }

    /**
     * Checks to see if the public key is not blank and contains
     * valid decimal and hex valules for this->hex & this->dec
     *
     * @return boolean
     */
    public function isValid()
    {
        return ((!empty($this->hex) && ctype_xdigit((string)$this->hex)) &&
            (!empty($this->dec) && ctype_digit((string)$this->dec)));
    }

    /**
     * @return SinKey
     * @throws Exception
     */
    public function getSin()
    {
        if (empty($this->hex)) {
            $this->generate();
        }

        if (null === $this->sin) {
            $this->sin = new SinKey();
            $this->sin->setPublicKey($this);
            $this->sin->generate();
        }

        return $this->sin;
    }

    /**
     * Generates an uncompressed and compressed EC public key.
     *
     * @param PrivateKey $privateKey
     *
     * @return PublicKey
     * @throws Exception
     */
    public function generate(PrivateKey $privateKey = null)
    {
        if ($privateKey instanceof PrivateKey) {
            $this->setPrivateKey($privateKey);
        }

        if (!empty($this->hex)) {
            return $this;
        }

        if (is_null($this->privateKey)) {
            throw new Exception('Please `setPrivateKey` before you generate a public key');
        }

        if (!$this->privateKey->isGenerated()) {
            $this->privateKey->generate();
        }

        if (!$this->privateKey->isValid()) {
            throw new Exception('Private Key is invalid and cannot be used to generate a public key');
        }

        $point = new Point(
            '0x' . substr(Secp256k1::G, 2, 64),
            '0x' . substr(Secp256k1::G, 66, 64)
        );

        $R = Util::doubleAndAdd(
            '0x' . $this->privateKey->getHex(),
            $point
        );

        $RxHex = Util::encodeHex($R->getX());
        $RyHex = Util::encodeHex($R->getY());

        $RxHex = str_pad($RxHex, 64, '0', STR_PAD_LEFT);
        $RyHex = str_pad($RyHex, 64, '0', STR_PAD_LEFT);

        $this->x = $RxHex;
        $this->y = $RyHex;
        $this->hex = sprintf('%s%s', $RxHex, $RyHex);
        $this->dec = Util::decodeHex($this->hex);

        return $this;
    }
}
