<?php

declare (strict_types=1);
namespace BitPayVendor\BitPaySDK\Functional;

use BitPayVendor\BitPaySDK\Client;
use BitPayVendor\PHPUnit\Framework\TestCase;
abstract class AbstractClientTestCase extends TestCase
{
    protected Client $client;
    /**
     * @throws \BitPaySDK\Exceptions\BitPayException
     */
    public function setUp() : void
    {
        $this->client = Client::createWithFile(Config::FUNCTIONAL_TEST_PATH . \DIRECTORY_SEPARATOR . Config::BITPAY_CONFIG_FILE);
    }
}
