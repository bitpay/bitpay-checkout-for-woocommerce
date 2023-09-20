<?php

/**
 * Copyright (c) 2019 BitPay
 **/
declare (strict_types=1);
namespace BitPayVendor\BitPaySDK\Util;

/**
 * @package BitPaySDK\Util
 * @author BitPay Integrations <integrations@bitpay.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class JsonMapperFactory
{
    public static function create() : \BitPayVendor\JsonMapper
    {
        $jsonMapper = new \BitPayVendor\JsonMapper();
        $jsonMapper->bEnforceMapType = \false;
        return $jsonMapper;
    }
}
