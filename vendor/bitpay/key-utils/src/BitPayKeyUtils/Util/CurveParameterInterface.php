<?php

namespace BitPayKeyUtils\Util;

/**
 */
interface CurveParameterInterface
{
    public function aHex();

    public function bHex();

    public function gHex();

    public function gxHex();

    public function gyHex();

    public function hHex();

    public function nHex();

    public function pHex();
}
