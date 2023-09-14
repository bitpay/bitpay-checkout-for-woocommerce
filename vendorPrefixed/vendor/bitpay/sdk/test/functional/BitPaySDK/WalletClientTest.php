<?php

/**
 * Copyright (c) 2019 BitPay
 **/
declare (strict_types=1);
namespace BitPayVendor\BitPaySDK\Functional;

class WalletClientTest extends AbstractClientTestCase
{
    public function testGetSupportedWallets() : void
    {
        $supportedWallets = $this->client->getSupportedWallets();
        self::assertNotNull($supportedWallets);
        self::assertIsArray($supportedWallets);
    }
}
