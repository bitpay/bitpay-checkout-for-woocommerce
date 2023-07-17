<?php

use BitPayKeyUtils\Util\Fingerprint;
use PHPUnit\Framework\TestCase;

class FingerprintTest extends TestCase
{
    public function testGenerate()
    {
        $fingerprint = new Fingerprint();
        $this->assertIsString($fingerprint::generate());
    }

    public function testGenerateIssetFinHash()
    {
        $expectedValue = 'ce9c26116feb916c356b5313226ff177bf30f819';

        $reflection = new \ReflectionProperty(Fingerprint::class, 'finHash');
        $reflection->setAccessible(true);
        $reflection->setValue(null, $expectedValue);

        $fingerprint = new Fingerprint();
        $actualValue = $fingerprint::generate();

        $this->assertIsString($actualValue);
        $this->assertEquals($expectedValue, $actualValue);
    }
}
