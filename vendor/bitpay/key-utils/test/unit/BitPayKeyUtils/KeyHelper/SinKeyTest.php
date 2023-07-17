<?php
declare(strict_types=1);

use BitPayKeyUtils\KeyHelper\PrivateKey;
use BitPayKeyUtils\KeyHelper\PublicKey;
use BitPayKeyUtils\KeyHelper\SinKey;
use PHPUnit\Framework\TestCase;

class SinKeyTest extends TestCase
{
    /**
     * @var SinKey $sinKey
     */
    private $sinKey;

    public function setUp(): void
    {
        $this->sinKey = new SinKey();
    }

    public function test__toStringEmpty(): void
    {
        $this->assertEmpty($this->sinKey->__toString());
    }

    public function test__toStringValue(): void
    {
        $publicKey = new PublicKey();
        $privateKey = new PrivateKey();
        $publicKey->generate($privateKey);
        $this->sinKey->setPublicKey($publicKey);
        $this->sinKey->generate();
        $property = $this->getAccessibleProperty(SinKey::class, 'value');
        $value = $property->getValue($this->sinKey);

        $this->assertEquals($value, $this->sinKey->__toString());
    }

    public function testSetPublicKey(): void
    {
        $publicKey = $this->getMockBuilder(PublicKey::class)->getMock();

        $this->assertEquals($this->sinKey, $this->sinKey->setPublicKey($publicKey));
    }

    public function testGenerateWithoutPublicKey(): void
    {
        $this->expectException(Exception::class);
        $this->sinKey->generate();
    }

    public function testPublicKeyGenerateException(): void
    {
        $property = $this->getAccessibleProperty(SinKey::class, 'publicKey');
        $property->setValue($this->sinKey, '');
        $this->expectException(Exception::class);
        $this->sinKey->generate();
    }

    public function testIsValid(): void
    {
        $publicKey = new PublicKey();
        $privateKey = new PrivateKey();
        $publicKey->generate($privateKey);
        $this->sinKey->setPublicKey($publicKey);
        $this->sinKey->generate();

        $this->assertEquals(true, $this->sinKey->isValid());
    }

    public function testIsValidFalse(): void
    {
        $this->assertEquals(false, $this->sinKey->isValid());
    }

    private function getAccessibleProperty(string $class, string $property): ReflectionProperty
    {
        $reflection = new ReflectionClass($class);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property;
    }
}