<?php

use BitPayKeyUtils\KeyHelper\Key;
use PHPUnit\Framework\TestCase;

class KeyTest extends TestCase
{
    public function testCreate()
    {
        $testedObject = $this->getTestedClass();
        $result = $testedObject::create();
        $this->assertInstanceOf(Key::class, $result);
    }

    public function testGetIdWhenIdNotSet()
    {
        $testedObject = $this->getTestedClass();
        $result = $testedObject->getId();
        $this->assertEmpty($result);
    }

    public function testGetIdWhenIdSet()
    {
        $id = 'test';
        $testedObject = $this->getTestedClass($id);
        $result = $testedObject->getId();
        $this->assertEquals($id, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetHex()
    {
        $testedObject = $this->getTestedClass();
        $exampleValue = 'test';

        $this->setProtectedPropertyValue($testedObject, 'hex', $exampleValue);
        $result = $testedObject->getHex();
        $this->assertEquals($exampleValue, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetDec()
    {
        $testedObject = $this->getTestedClass();
        $exampleValue = 'test';

        $this->setProtectedPropertyValue($testedObject, 'dec', $exampleValue);
        $result = $testedObject->getDec();
        $this->assertEquals($exampleValue, $result);
    }

    /**
     * @throws Exception
     */
    public function testSerialize()
    {
        $exampleId = 'test';

        $testedObject = $this->getTestedClass($exampleId);
        $result = $testedObject->serialize();
        $this->assertIsString($result);
        $this->assertStringContainsString($exampleId, $result);
    }

    public function testUnserialize()
    {
        $data = serialize(['id', 'x', 'y', 'hex', 'dec']);

        $testedObject = $this->getTestedClass();
        $this->assertEmpty($testedObject->getId());

        $testedObject->unserialize($data);

        $this->assertEquals('id', $testedObject->getId());
        $this->assertEquals('x', $testedObject->getX());
        $this->assertEquals('y', $testedObject->getY());
        $this->assertEquals('hex', $testedObject->getHex());
        $this->assertEquals('dec', $testedObject->getDec());
    }

    /**
     * @throws ReflectionException
     */
    public function testIsGenerated()
    {
        $testedObject = $this->getTestedClass();
        $this->assertIsBool($testedObject->isGenerated());

        $this->setProtectedPropertyValue($testedObject, 'hex', 'test');
        $this->assertTrue($testedObject->isGenerated());
    }

    /**
     * @throws ReflectionException
     */
    private function setProtectedPropertyValue(&$instance, $propertyName, $propertyValue)
    {
        $reflection = new \ReflectionProperty(get_class($instance), $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($instance, $propertyValue);
    }

    public function getTestedClass($id = null): Key
    {
        return new class($id) extends Key {
            public function generate(): bool
            {
                return true;
            }

            public function isValid(): bool
            {
                return true;
            }
        };
    }
}