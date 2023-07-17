<?php

namespace BitPayKeyUtils\Math;

class GmpEngine implements EngineInterface
{
    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function add($a, $b)
    {
        return gmp_strval(gmp_add($a, $b));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function cmp($a, $b)
    {
        return gmp_strval(gmp_cmp($a, $b));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @param int $round
     * @return string
     */
    public function div($a, $b, $round = GMP_ROUND_ZERO)
    {
        return gmp_strval(gmp_div_q($a, $b, $round));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function invertm($a, $b)
    {
        return gmp_strval(gmp_invert($a, $b));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function mod($a, $b)
    {
        return gmp_strval(gmp_mod($a, $b));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function mul($a, $b)
    {
        return gmp_strval(gmp_mul($a, $b));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function pow($a, $b)
    {
        return gmp_strval(gmp_pow($a, $b));
    }

    /**
     * @param String $a Numeric String
     * @param String $b Numeric String
     * @return string
     */
    public function sub($a, $b)
    {
        return gmp_strval(gmp_sub($a, $b));
    }
}
