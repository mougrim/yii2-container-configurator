<?php
namespace mougrim\yii2ContainerConfigurator;

use PHPUnit_Framework_TestCase;

/**
 * @package mougrim\yii2ContainerConfigurator
 * @author Mougrim <rinat@mougrim.ru>
 */
class ContainerConfiguratorTestStub
{
    private $setedProperties = [];
    private $calledMethods = [];
    private $constructParams = [];

    public function __construct($param0 = null, $param1 = null, $param2 = null)
    {
        if ($param0 !== null) {
            $this->constructParams[0] = $param0;
        }
        if ($param1 !== null) {
            $this->constructParams[1] = $param1;
        }
        if ($param2 !== null) {
            $this->constructParams[2] = $param2;
        }
    }

    public function __set($propertyName, $propertyValue)
    {
        $this->setedProperties[$propertyName][] = $propertyValue;
    }

    public function __call($methodName, $params)
    {
        $this->calledMethods[$methodName][] = $params;
    }

    public static function test(PHPUnit_Framework_TestCase $test, array $config, ContainerConfiguratorTestStub $stub)
    {
        $test->assertEquals($config['class'], get_class($stub));

        if (isset($config['arguments'])) {
            foreach ($config['arguments'] as $argumentNumber => $argument) {
                $test->assertTrue(isset($stub->constructParams[$argumentNumber]));
                $test->assertSame($argument, $stub->constructParams[$argumentNumber]);
                unset($stub->constructParams[$argumentNumber]);
            }
        }
        $test->assertEmpty($stub->constructParams);

        if (isset($config['properties'])) {
            foreach ($config['properties'] as $propertyName => $propertyValue) {
                $test->assertTrue(isset($stub->setedProperties[$propertyName]));
                $test->assertEquals(1, count($stub->setedProperties[$propertyName]));
                $test->assertTrue(array_key_exists(0, $stub->setedProperties[$propertyName]));
                $test->assertSame($propertyValue, $stub->setedProperties[$propertyName][0]);
                unset($stub->setedProperties[$propertyName]);
            }
        }
        $test->assertEmpty($stub->setedProperties);

        if (isset($config['methods'])) {
            foreach ($config['methods'] as $methodName => $methodParams) {
                $test->assertTrue(isset($stub->calledMethods[$methodName]));
                $test->assertEquals(1, count($stub->calledMethods[$methodName]));
                $test->assertTrue(array_key_exists(0, $stub->calledMethods[$methodName]));
                foreach ($methodParams as $paramNumber => $paramValue) {
                    $test->assertTrue(isset($stub->calledMethods[$methodName][0][$paramNumber]));
                    $test->assertSame($paramValue, $stub->calledMethods[$methodName][0][$paramNumber]);
                    unset($stub->calledMethods[$methodName][0][$paramNumber]);
                }
                $test->assertEmpty($stub->calledMethods[$methodName][0]);
                unset($stub->calledMethods[$methodName]);
            }
        }
        $test->assertEmpty($stub->calledMethods);
    }
}
