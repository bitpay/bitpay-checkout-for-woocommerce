<?php

use BitPayKeyUtils\Util\Point;
use PHPUnit\Framework\TestCase;

class PointTest extends TestCase
{
    public function testInstanceOf(){
        $point = $this->createClassObject();
        $this->assertInstanceOf(Point::class, $point);
    }

    public function test__toString()
    {
        $point = new Point('3', '2');
        $this->assertEquals('(3, 2)', $point->__toString());
    }

    public function test__toStringInfinite()
    {
        $point = new Point('inf', '2');
        $this->assertEquals('inf', $point->__toString());
    }

    public function testIsInfinityFalse()
    {
        $point = $this->createClassObject();
        $this->assertFalse($point->isInfinity());
    }

    public function testIsInfinityTrue()
    {
        $point = new Point('inf', '4');
        $this->assertTrue($point->isInfinity());
    }

    public function testGetX()
    {
        $point = $this->createClassObject();
        $this->assertEquals('-2', $point->getX());
    }

    public function testGetY()
    {
        $point = $this->createClassObject();
        $this->assertEquals('3', $point->getY());
    }

    public function testSerialize()
    {
        $expectedValue = 'a:2:{i:0;s:2:"-2";i:1;s:1:"3";}';

        $point = $this->createClassObject();
        $this->assertEquals($expectedValue, $point->serialize());
    }

    public function testUnserialize()
    {
        $expectedValue = '[-2, 3]';
        $testedData = 'a:2:{i:0;s:2:"-2";i:1;s:1:"3";}';

        $point = $this->createClassObject();
        $this->assertEquals(null, $point->unserialize($testedData));
    }

    public function test__serialize()
    {
        $expectedValue = ['-2', '3'];

        $point = $this->createClassObject();
        $this->assertEquals($expectedValue, $point->__serialize());
    }

    public function test__unserialize()
    {
        $expectedValue = ['-2', '3'];

        $point = $this->createClassObject();
        $this->assertEquals(null, $point->__unserialize(['-2', '3']));
    }

    private function createClassObject()
    {
        return new Point('-2', '3');
    }
}
