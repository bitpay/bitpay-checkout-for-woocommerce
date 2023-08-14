<?php

declare (strict_types=1);
namespace BitPayVendor\StubTests;

use BitPayVendor\PHPUnit\Framework\Exception;
use RuntimeException;
use BitPayVendor\StubTests\Model\PHPClass;
use BitPayVendor\StubTests\Model\PHPConst;
use BitPayVendor\StubTests\Model\PHPInterface;
use BitPayVendor\StubTests\TestData\Providers\PhpStormStubsSingleton;
class BaseConstantsTest extends AbstractBaseStubsTestCase
{
    /**
     * @dataProvider \StubTests\TestData\Providers\Reflection\ReflectionConstantsProvider::constantProvider
     * @throws Exception
     */
    public function testConstants(PHPConst $constant) : void
    {
        $constantName = $constant->name;
        $constantValue = $constant->value;
        $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getConstant($constantName);
        static::assertNotEmpty($stubConstant, "Missing constant: const {$constantName} = {$constantValue}\n");
    }
    /**
     * @dataProvider \StubTests\TestData\Providers\Reflection\ReflectionConstantsProvider::classConstantProvider
     * @throws Exception|RuntimeException
     */
    public function testClassConstants(PHPClass|PHPInterface $class, PHPConst $constant) : void
    {
        $constantName = $constant->name;
        $constantValue = $constant->value;
        if ($class instanceof PHPClass) {
            $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getClass($class->name)->getConstant($constantName);
        } else {
            $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($class->name)->getConstant($constantName);
        }
        static::assertNotEmpty($stubConstant, "Missing constant: const {$constantName} = {$constantValue}\n");
    }
    /**
     * @dataProvider \StubTests\TestData\Providers\Reflection\ReflectionConstantsProvider::classConstantProvider
     * @throws RuntimeException
     */
    public function testClassConstantsVisibility(PHPClass|PHPInterface $class, PHPConst $constant) : void
    {
        $constantName = $constant->name;
        $constantVisibility = $constant->visibility;
        if ($class instanceof PHPClass) {
            $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getClass($class->name)->getConstant($constantName);
        } else {
            $stubConstant = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($class->name)->getConstant($constantName);
        }
        static::assertEquals($constantVisibility, $stubConstant->visibility, "Constant visibility mismatch: const {$constantName} \n\n            Expected visibility: {$constantVisibility} but was {$stubConstant->visibility}");
    }
}
