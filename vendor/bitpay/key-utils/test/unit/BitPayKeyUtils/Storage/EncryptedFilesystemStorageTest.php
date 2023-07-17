<?php

use BitPayKeyUtils\KeyHelper\Key;
use BitPayKeyUtils\Storage\EncryptedFilesystemStorage;
use PHPUnit\Framework\TestCase;

class EncryptedFilesystemStorageTest extends TestCase
{
    public function testInstanceOf()
    {
        $encryptedFilesystemStorage = $this->createClassObject();
        $this->assertInstanceOf(EncryptedFilesystemStorage::class, $encryptedFilesystemStorage);
    }

    public function testPersist()
    {
        $encryptedFilesystemStorage = $this->createClassObject();
        $keyInterface = $this->getMockBuilder(Key::class)->getMock();
        $keyInterface->method('getId')->willReturn(__DIR__ . '/test11.txt');
        $this->assertFileExists(__DIR__ . '/test11.txt');
        $this->assertEquals(null, $encryptedFilesystemStorage->persist($keyInterface));

    }

    public function testLoadNotFindException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not find "'.__DIR__.'/test2.txt"');

        $encryptedFilesystemStorage = $this->createClassObject();
        $encryptedFilesystemStorage->load(__DIR__ . '/test2.txt');
    }

    // This test needs the user(not root) and the corresponding permissions (file cannot be read)
    /**
    public function testLoadNotPermissionException()
    {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('"' . __DIR__ . '/test3.txt" cannot be read, check permissions');
    $filesystemStorage = $this->createClassObject();
    $filesystemStorage->load(__DIR__ . '/test3.txt');
    }
     **/

    public function testLoad()
    {
       $encryptedFilesystemStorage = $this->createClassObject();
       $this->assertIsObject($encryptedFilesystemStorage->load(__DIR__ . '/test11.txt'));
    }

    private function createClassObject()
    {
        return new EncryptedFilesystemStorage('test');
    }
}
