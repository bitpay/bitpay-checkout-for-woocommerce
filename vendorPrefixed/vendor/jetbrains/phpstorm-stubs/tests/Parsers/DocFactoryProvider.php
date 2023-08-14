<?php

declare (strict_types=1);
namespace BitPayVendor\StubTests\Parsers;

use BitPayVendor\phpDocumentor\Reflection\DocBlockFactory;
use BitPayVendor\StubTests\Model\Tags\RemovedTag;
class DocFactoryProvider
{
    private static ?DocBlockFactory $docFactory = null;
    public static function getDocFactory() : DocBlockFactory
    {
        if (self::$docFactory === null) {
            self::$docFactory = DocBlockFactory::createInstance(['removed' => RemovedTag::class]);
        }
        return self::$docFactory;
    }
}
