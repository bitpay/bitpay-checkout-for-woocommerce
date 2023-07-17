<?php

use BitPayKeyUtils\Math\BcEngine;
use PHPUnit\Framework\TestCase;

class BcEngineTest extends TestCase
{
    public function testInstanceOf()
    {
        $bcEngine = $this->createClassObject();
        $this->assertInstanceOf(BcEngine::class, $bcEngine);
    }

    public function testAdd()
    {
        $expectedValue = '12';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->add('5', '7'));
    }

    public function testInput()
    {
        $expectedValue = '5';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->input($expectedValue));
    }

    public function testInputNull()
    {
        $expectedValue = '0';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->input(null));
    }

    public function testInputHex()
    {
        $expectedValue = '86';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->input('0x56'));
    }

    public function testInputException()
    {
        $this->expectException(Exception::class);

        $bcEngine = $this->createClassObject();
        $bcEngine->input('Teg4ew');
    }

    public function testCmpGreaterThan()
    {
        $expectedValue = '1';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->cmp('9', '7'));
    }

    public function testCmpLessThan()
    {
        $expectedValue = '-1';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->cmp('7', '9'));
    }

    public function testCmpEqualsTo()
    {
        $expectedValue = '0';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->cmp('7', '7'));
    }

    public function testDiv()
    {
        $expectedValue = '3';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->div('6', '2'));
    }

    public function testInvertm()
    {
        $expectedValue = '0';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->invertm('6', '2'));
    }

    public function testInvertm2()
    {
        $expectedValue = '1';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->invertm('-1', '2'));
    }

    public function testMod()
    {
        $expectedValue = '0';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->mod('6', '2'));
    }

    public function testMod2()
    {
        $expectedValue = '2';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->mod('-6', '2'));
    }

    public function testMul()
    {
        $expectedValue = '21';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->mul('3', '7'));
    }

    public function testPow()
    {
        $expectedValue = '64';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->pow('4', '3'));
    }

    public function testSub()
    {
        $expectedValue = '18';

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->sub('20', '2'));
    }

    public function testCoprime()
    {
        $expectedValue = false;

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->coprime('20', '2'));
    }

    public function testCoprime2()
    {
        $expectedValue = true;

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->coprime('5', '3'));
    }

    public function testCoprime3()
    {
        $expectedValue = true;

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->coprime('3', '5'));
    }

    public function testCoprime4()
    {
        $expectedValue = false;

        $bcEngine = $this->createClassObject();
        $this->assertEquals($expectedValue, $bcEngine->coprime('3', '3'));
    }

    private function createClassObject()
    {
        return new BcEngine();
    }
}
