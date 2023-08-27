<?php

declare (strict_types=1);
namespace BitPayVendor\StubTests\TestData\Providers;

use BitPayVendor\StubTests\Model\StubsContainer;
use BitPayVendor\StubTests\Parsers\StubParser;
class PhpStormStubsSingleton
{
    private static ?StubsContainer $phpstormStubs = null;
    public static function getPhpStormStubs() : StubsContainer
    {
        if (self::$phpstormStubs === null) {
            self::$phpstormStubs = StubParser::getPhpStormStubs();
        }
        return self::$phpstormStubs;
    }
}
