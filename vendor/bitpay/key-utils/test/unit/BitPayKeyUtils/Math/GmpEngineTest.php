<?php

use BitPayKeyUtils\Math\GmpEngine;
use PHPUnit\Framework\TestCase;

class GmpEngineTest extends TestCase
{
    public function testAdd()
    {
        $expectedResult = '5';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->add('2', '3');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCmpGreaterThan()
    {
        $expectedResult = '1';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->cmp('1234', '1000');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCmpLessThan()
    {
        $expectedResult = '-1';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->cmp('1000', '1234');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCmpEqualTo()
    {
        $expectedResult = '0';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->cmp('1000', '1000');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testDiv()
    {
        $expectedResult = '2';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->div('10', '5');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testInvertm()
    {
        $expectedResult = '9';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->invertm('5', '11');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testMod()
    {
        $expectedResult = '3';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->mod('7', '4');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testMul()
    {
        $expectedResult = '16';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->mul('8', '2');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testPow()
    {
        $expectedResult = '27';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->pow('3', '3');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testSub()
    {
        $expectedResult = '42';
        $gmp = $this->createClassObject();
        $actualResult = $gmp->sub('64', '22');

        $this->assertIsString($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    private function createClassObject()
    {
        return new GmpEngine();
    }
}
