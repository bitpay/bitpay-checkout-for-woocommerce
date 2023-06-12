<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Util\ErrorHandler;
use BitPayKeyUtils\Util\Error;

class ErrorTest extends TestCase
{
    public function testBacktraceWhenPrintIsFalse()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->backtrace();
        $this->assertEquals('backtrace', $result[0]['function']);
    }

    public function testBacktraceWhenPrintIsTrue()
    {
        $testedObject = $this->getTestedClassObject();
        ob_start();
        $testedObject->backtrace(true);
        $result = ob_get_clean();
        $this->assertIsString($result);
    }

    public function testLastWhenNoErrorOccured()
    {
        error_clear_last();
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->last();
        $this->assertNull($result);
    }

    public function testLog()
    {
        $exampleLogMessage = 'test';
        $testedObject = $this->getTestedClassObject();
        $errorLogTemporaryFile = tmpfile();
        $errorLogLocationBackup = ini_set('error_log', stream_get_meta_data($errorLogTemporaryFile)['uri']);
        $testedObject->log($exampleLogMessage);
        ini_set('error_log', $errorLogLocationBackup);
        $result = stream_get_contents($errorLogTemporaryFile);

        $this->assertStringContainsString($exampleLogMessage, $result);
    }

    public function testReportingWithNoParam()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->reporting();
        $this->assertEquals(error_reporting(), $result);
    }

    public function testReportingWithLevel()
    {
        $testedObject = $this->getTestedClassObject();
        $exampleReportingLevel = 32767;
        $testedObject->reporting($exampleReportingLevel);
        $currentLevel = error_reporting();
        $this->assertEquals($exampleReportingLevel, $currentLevel);
    }

    public function testHandlerWithNoParams()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler();
        $this->assertTrue($result);
    }

    public function testHandlerWithActionSet()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler('error', 'set', null);
        $this->assertInstanceOf(ErrorHandler::class, $result);
    }

    public function testHandlerWithActionFalse()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler('error', false);
        $this->assertFalse($result);
    }

    public function testHandlerWithTypeExceptionAndNoAction()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler('exception', false);
        $this->assertFalse($result);
    }

    public function testHandlerWithTypeExceptionAndActionSet()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler('exception', 'set', null);
        $this->assertNull($result);
    }

    public function testHandlerWithTypeExceptionAndActionRestore()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler('exception', 'restore', null);
        $this->assertTrue($result);
    }

    public function testHandlerWithUnhandledType()
    {
        $testedObject = $this->getTestedClassObject();
        $result = $testedObject->handler('asd', 'restore', null);
        $this->assertFalse($result);
    }

    public function testRaise()
    {
        $testedObject = $this->getTestedClassObject();
        $this->expectError();
        $result = $testedObject->raise('error');
        $this->assertTrue($result);
    }

    private function getTestedClassObject(): Error
    {
        return new Error();
    }
}
