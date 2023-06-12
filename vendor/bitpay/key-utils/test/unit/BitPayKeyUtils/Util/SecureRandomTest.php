<?php

use BitPayKeyUtils\Util\SecureRandom;
use PHPUnit\Framework\TestCase;

class SecureRandomTest extends TestCase
{
    public function testInstanceOf()
    {
        $secureRandom = $this->createClassObject();
        $this->assertInstanceOf(SecureRandom::class, $secureRandom);
    }

    public function testHasOpenSSL()
    {
        $secureRandom = $this->createClassObject();
        $secureRandom::hasOpenSSL();

        $reflection = new ReflectionProperty($secureRandom, 'hasOpenSSL');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->getValue());
        $this->assertObjectHasAttribute('hasOpenSSL', $secureRandom);
    }

    public function testGenerateRandom()
    {
        $secureRandom = $this->createClassObject();
        $secureRandom::generateRandom();
        $this->assertIsString($secureRandom::generateRandom());
    }

    public function testGenerateRandomException()
    {
        $this->expectException(Exception::class);

        $reflection = new \ReflectionProperty(SecureRandom::class, 'hasOpenSSL');
        $reflection->setAccessible(true);
        $reflection->setValue(null, false);

        $secureRandom = $this->createClassObject();
        $secureRandom::generateRandom();
    }

    private function createClassObject()
    {
        return new SecureRandom();
    }
}
