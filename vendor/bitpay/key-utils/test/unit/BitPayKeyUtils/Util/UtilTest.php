<?php

use BitPayKeyUtils\Util\Point;
use BitPayKeyUtils\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testInstanceOf()
    {
        $util = $this->createClassObject();
        $this->assertInstanceOf(Util::class, $util);
    }

    public function testSha512()
    {
        $expectedValue = 'ee26b0dd4af7e749aa1a8ee3c10ae9923f618980772e473f8819a5d4940e0db27ac185f8a0e1d5f84f88bc887fd67b143732c304cc5fa9ad8e6f57f50028a8ff';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::sha512('test'));
    }

    public function testSha512hmac()
    {
        $expectedValue = '287a0fb89a7fbdfa5b5538636918e537a5b83065e4ff331268b7aaa115dde047a9b0f4fb5b828608fc0b6327f10055f7637b058e9e0dbb9e698901a3e6dd461c';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::sha512hmac('test', 'key'));
    }

    public function testSha256ripe160()
    {
        $expectedValue = 'cebaa98c19807134434d107b0d3e5692a516ea66';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::sha256ripe160('test'));
    }

    public function testRipe160()
    {
        $expectedValue = '5e52fee47e6b070565f74372468cdc699de89107';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::ripe160('test'));
    }

    public function testSha256()
    {
        $expectedValue = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::sha256('test'));
    }

    public function testTwoSha256()
    {
        $expectedValue = '7b3d979ca8330a94fa7e9e1b466d8b99e0bcdea1ec90596c0dcc8d7ef6b4300c';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::twoSha256('test'));
    }

    public function testNonce()
    {
        $util = $this->createClassObject();
        $this->assertIsFloat($util::nonce());
    }

    public function testGuid()
    {
        $util = $this->createClassObject();
        $this->assertIsString($util::guid());
    }

    public function testEncodeHexException()
    {
        $this->expectException(Exception::class);

        $util = $this->createClassObject();
        $util::encodeHex(null);
    }

    public function testEncodeHex1()
    {
        $expectedValue = '7b';
        $util = $this->createClassObject();

        $this->assertEquals($expectedValue, $util::encodeHex('-123'));
    }

    public function testEncodeHex2()
    {
        $expectedValue = '1c2';
        $util = $this->createClassObject();

        $this->assertEquals($expectedValue, $util::encodeHex('450'));
    }

    public function testDecToBin()
    {
        $expectedValue = '0011111';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::decToBin('124'));
    }

    public function testDecodeHex()
    {
        $expectedValue = '257';

        $util = $this->createClassObject();
        $this->assertEquals($expectedValue, $util::decodeHex('00101'));
    }

    public function testPointDouble()
    {
        $pointInterface = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterface->method('getX')->willReturn('5');
        $pointInterface->method('getY')->willReturn('3');

        $util = $this->createClassObject();
        $point = $util::pointDouble($pointInterface);
        $this->assertIsObject($point);
        $this->assertEquals('28948022309329048855892746252171976963317496166410141009864396001977208668062', $point->getX());
        $this->assertEquals('43422033463993573283839119378257965444976244249615211514796594002965813000105', $point->getY());
    }

    public function testPointAdd()
    {
        $pointInterfaceP = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterfaceP->method('getX')->willReturn('6');
        $pointInterfaceP->method('getY')->willReturn('9');

        $pointInterfaceQ = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterfaceQ->method('getX')->willReturn('8');
        $pointInterfaceQ->method('getY')->willReturn('2');

        $util = $this->createClassObject();
        $point = $util::pointAdd($pointInterfaceP, $pointInterfaceQ);
        $this->assertIsObject($point);
        $this->assertEquals('28948022309329048855892746252171976963317496166410141009864396001977208667914', $point->getX());
        $this->assertEquals('101318078082651670995624611882601919371611236582435493534525386006920230337669', $point->getY());
    }

    public function testPointAddSameValues()
    {
        $pointInterfaceP = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterfaceP->method('getX')->willReturn('5');
        $pointInterfaceP->method('getY')->willReturn('3');

        $pointInterfaceQ = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterfaceQ->method('getX')->willReturn('5');
        $pointInterfaceQ->method('getY')->willReturn('3');

        $util = $this->createClassObject();
        $point = $util::pointAdd($pointInterfaceP, $pointInterfaceQ);
        $this->assertIsObject($point);
        $this->assertEquals('28948022309329048855892746252171976963317496166410141009864396001977208668062', $point->getX());
        $this->assertEquals('43422033463993573283839119378257965444976244249615211514796594002965813000105', $point->getY());
    }

    public function testPointAddInfinity()
    {
        $pointInterfaceP = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterfaceP->method('isInfinity')->willReturn(false);
        $pointInterfaceP->method('getX')->willReturn('1');
        $pointInterfaceP->method('getY')->willReturn('2');

        $pointInterfaceQ = $this->getMockBuilder(Point::class)->disableOriginalConstructor()->getMock();
        $pointInterfaceQ->method('isInfinity')->willReturn(true);

        $util = $this->createClassObject();
        $point = $util::pointAdd($pointInterfaceP, $pointInterfaceQ);
        $this->assertIsObject($point);
        $this->assertEquals('1', $point->getX());
        $this->assertEquals('2', $point->getY());
    }

    public function testBinConv()
    {
        $util = $this->createClassObject();
        $this->assertIsString($util::binConv('FF'));
    }

    public function testCheckRequirements()
    {
        $util = $this->createClassObject();
        $this->assertIsArray($util::checkRequirements());
    }

    private function createClassObject()
    {
        return new Util();
    }
}
