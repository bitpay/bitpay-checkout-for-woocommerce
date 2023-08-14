<?php

declare (strict_types=1);
namespace BitPayVendor\StubTests\Parsers;

use BitPayVendor\PhpParser\Error;
use BitPayVendor\PhpParser\ErrorHandler;
class StubsParserErrorHandler implements ErrorHandler
{
    public function handleError(Error $error) : void
    {
        $error->setRawMessage($error->getRawMessage() . "\n" . $error->getFile());
    }
}
