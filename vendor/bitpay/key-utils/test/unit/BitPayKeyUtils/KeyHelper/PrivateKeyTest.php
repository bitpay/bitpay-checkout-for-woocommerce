<?php

use BitPayKeyUtils\KeyHelper\PrivateKey;
use BitPayKeyUtils\KeyHelper\PublicKey;
use PHPUnit\Framework\TestCase;

class PrivateKeyTest extends TestCase
{
	const EXAMPLE_HEX_STRING = '4f0a88d37c05095b';
	const EXAMPLE_ECDSA_KEY = '-----BEGIN EC PRIVATE KEY-----MHQCAQEEIFL3sLnioGcDvHWM/BPlNw96BOx1KKco2qsq4UwhQUosoAcGBSuBBAAK
	oUQDQgAEXs1Fmq4QdPAbn3NycdEU+HOjc3kW9efbso2kI/vdDTWcSCMk310s53G3tRClDBPPuuJAsKghbPfaTaUpmXFCNA==-----END EC PRIVATE KEY-----';
	
	public function testSetHex()
	{
		$testedObject = $this->createClassObject();
		$exampleValue = 'test';
		
		$testedObject->setHex( $exampleValue );
		$realValue = $this->accessProtected( $testedObject, 'hex' );
		$this->assertEquals( $exampleValue, $realValue );
	}
	
	/**
	 * @throws Exception
	 */
	public function testGenerateReturnsObjectWithRandomHexIfHexIsNotSet()
	{
		$testedObject = $this->createClassObject();
		$result       = $testedObject->generate();
		$realValue    = $this->accessProtected( $result, 'hex' );
		$this->assertIsString( $realValue );
	}
	
	/**
	 * @throws Exception
	 */
	public function testGenerateReturnsObjectWithHexIfHexIsSet()
	{
		$testedObject  = $this->createClassObject();
		$expectedValue = 'test';
		
		$testedObject->setHex( $expectedValue );
		$result    = $testedObject->generate();
		$realValue = $this->accessProtected( $result, 'hex' );
		$this->assertEquals( $expectedValue, $realValue );
	}
	
	public function testIsValidIfKeyNotSet()
	{
		$testedObject = $this->createClassObject();
		$this->assertFalse( $testedObject->isValid() );
	}
	
	public function testHasValidHexIfHexNotSet()
	{
		$testedObject = $this->createClassObject();
		$this->assertFalse( $testedObject->hasValidHex() );
	}
	
	public function testIsValid()
	{
		$testedObject = $this->createClassObject();
		$testedObject->setHex( self::EXAMPLE_HEX_STRING );
		$this->assertTrue( $testedObject->isValid() );
	}
	
	public function testSign()
	{
		$testedObject = $this->createClassObject();
		$testedObject->setHex( self::EXAMPLE_HEX_STRING );
		
		try
		{
			$result = $testedObject->sign( 'test' );
		}
		catch ( Exception $exception )
		{
			$this->fail();
		}
		
		$this->assertIsString( $result );
	}
	
	public function testSignWhenNoHex()
	{
		$testedObject = $this->createClassObject();
		
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'The private key must be in hex format.' );
		
		$testedObject->sign( 'test' );
	}
	
	public function testSignWhenDataEmpty()
	{
		$testedObject = $this->createClassObject();
		$testedObject->setHex( self::EXAMPLE_HEX_STRING );
		
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'You did not provide any data to sign' );
		
		$testedObject->sign( [] );
	}
	
	public function testPemDecode()
	{
		$testedObject = $this->createClassObject();
		
		$result = $testedObject->pemDecode( self::EXAMPLE_ECDSA_KEY );
		
		$this->assertIsArray( $result );
		$this->assertEquals( '52f7b0b9e2a06703bc758cfc13e5370f7a04ec7528a728daab2ae14c21414a2c', $result[ 'private_key' ] );
		$this->assertEquals(
			'045ecd459aae1074f01b9f737271d114f873a3737916f5e7dbb28da423fbdd0d359c482324df5d2ce771b7b510a50c13cfbae240b0a8216cf7da4da52999714234',
			$result[ 'public_key' ] );
	}
	
	public function testPemDecodeTooShortPemException()
	{
		$testedObject = $this->createClassObject();
		
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid or corrupt secp256k1 key provided. Cannot decode the supplied PEM data.' );
		$testedObject->pemDecode( 'test' );
	}
	
	public function testPemDecodePemShouldThrowExceptionWhenCorruptKeyProvided()
	{
		$testedObject = $this->createClassObject();
		
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid or corrupt secp256k1 key provided. Cannot decode the supplied PEM data.' );
		$testedObject->pemDecode(
			'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
		);
	}
	
	public function testPemEncode()
	{
		$testedObject = $this->createClassObject();
		
		$beg_ec_text = '-----BEGIN EC PRIVATE KEY-----';
		$end_ec_text = '-----END EC PRIVATE KEY-----';
		
		$exampleKeyPair = [ '52f7b0b9e2a06703bc758cfc13e5370f7a04ec7528a728daab2ae14c21414a2c',
		                    '045ecd459aae1074f01b9f737271d114f873a3737916f5e7dbb28da423fbdd0d359c482324df5d2ce771b7b510a50c13cfbae240b0a8216cf7da4da529997142',
		];
		
		$result = $testedObject->pemEncode( $exampleKeyPair );
		$this->assertIsString( $result );
		$this->assertStringContainsString( $beg_ec_text, $result );
		$this->assertStringContainsString( $end_ec_text, $result );
	}
	
	public function testPemEncodeCorruptSecp()
	{
		$testedObject = $this->createClassObject();
		
		$exampleCorruptKeyPair = [ '52f7b0b9e2a06703bc758cfc13e5370f7a04ec7528a728daab2ae14c21414a2c',
		                           '7dbb28da423fbdd0d359c482324df5d2ce771b7b510a50c13cfbae240b0a8216cf7da4da52999714234',
		];
		
		$this->expectExceptionMessage( 'Invalid or corrupt secp256k1 keypair provided. Cannot decode the supplied PEM data.' );
		$this->expectException( Exception::class );
		$testedObject->pemEncode( $exampleCorruptKeyPair );
	}
	
	public function test__toString()
	{
		$testedObject = $this->createClassObject();
		
		$this->assertEmpty( (string) $testedObject );
	}
	
	public function test__toStringWhenHexIsSet()
	{
		$testedObject = $this->createClassObject();
		$testedObject->setHex( self::EXAMPLE_HEX_STRING );
		
		$this->assertEquals( self::EXAMPLE_HEX_STRING, (string) $testedObject );
	}
	
	public function testGetPublicKeyIfKeyIsNull()
	{
		$testedObject = $this->createClassObject();
		$result       = $testedObject->getPublicKey();
		$this->assertInstanceOf( PublicKey::class, $result );
	}
	
	public function testGetPublicKeyIfKeyIsNotNull()
	{
		$testedObject = $this->createClassObject();
		
		$firstResult  = $testedObject->getPublicKey();
		$secondResult = $testedObject->getPublicKey();
		
		$this->assertInstanceOf( PublicKey::class, $firstResult );
		$this->assertEquals( $firstResult, $secondResult );
	}
	
	private function createClassObject(): PrivateKey
	{
		return new PrivateKey();
	}
	
	private function accessProtected( $obj, $prop )
	{
		$reflection = new ReflectionClass( $obj );
		$property   = $reflection->getProperty( $prop );
		$property->setAccessible( true );
		
		return $property->getValue( $obj );
	}
}