<?php

use BitPayKeyUtils\Util\Base58;
use PHPUnit\Framework\TestCase;

class Base58Test extends TestCase
{
    public function testInstanceOf()
    {
        $base58 = $this->createClassObject();
        $this->assertInstanceOf(Base58::class, $base58);
    }

    public function testEncodeException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Length');

        $base58 = $this->createClassObject();
        $base58->encode('0x16p4t');
    }

    public function testEncode()
    {
        $base58 = $this->createClassObject();
        $this->assertEquals('P', $base58->encode('0x16'));
    }

    public function testEncode2()
    {
        $base58 = $this->createClassObject();
        $this->assertEquals('1', $base58->encode('00'));
    }

    public function testDecode()
    {
        $base58 = $this->createClassObject();
        $this->assertEquals('4f59cb', $base58->decode('Test'));
    }

    public function testDecode2()
    {
        $base58 = $this->createClassObject();
        $this->assertEquals('02bf547c6d249ea9', $base58->decode('Test 5 T 2'));
    }

    public function testDecode3()
    {
        $base58 = $this->createClassObject();
        $this->assertEquals('00', $base58->decode('1'));
    }

    private function createClassObject()
    {
        return new Base58();
    }
}
