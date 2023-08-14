<?php

namespace BitPayVendor;

use BitPayVendor\BitPayKeyUtils\Math\GmpEngine;
use BitPayVendor\BitPayKeyUtils\Math\Math;
use BitPayVendor\PHPUnit\Framework\TestCase;
class MathTest extends TestCase
{
    public function testInstanceOf()
    {
        $math = $this->createClassObject();
        $this->assertInstanceOf(Math::class, $math);
    }
    public function testGetEngine()
    {
        $expectedEngine = new GmpEngine();
        $math = $this->createClassObject();
        $math::setEngine($expectedEngine);
        $this->assertEquals($expectedEngine, $math::getEngine());
    }
    public function testGetEngineName()
    {
        $expectedEngineName = 'Test engine name';
        $math = $this->createClassObject();
        $math::setEngineName($expectedEngineName);
        $this->assertEquals($expectedEngineName, $math::getEngineName());
    }
    private function createClassObject()
    {
        return new Math();
    }
}
\class_alias('BitPayVendor\\MathTest', 'MathTest', \false);
