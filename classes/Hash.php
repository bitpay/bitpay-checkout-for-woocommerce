<?php

class Hash
{
    private $hash;

    public function __construct($hash = null)
    {
        $this->hash_obj = null;

    }

    public function setHash($hash)
    {
        $bitpayhash = password_hash($hash, PASSWORD_DEFAULT);
        $this->hash_obj = new stdClass();
        $this->hash_obj->verificationHash = $bitpayhash;
        $this->hash_obj = base64_encode(json_encode($this->hash_obj));
        return $this->hash_obj;
    }

    public function getHash($hash, $obj)
    {
        $decrypted = base64_decode($obj);
        $decrypted = json_decode($decrypted);
        if (password_verify($hash, $decrypted->verificationHash)):
            return true;
        else:
            return false;
        endif;
    }
}
