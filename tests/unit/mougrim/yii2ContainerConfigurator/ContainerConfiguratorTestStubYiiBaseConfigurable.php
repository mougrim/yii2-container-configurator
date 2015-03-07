<?php
namespace mougrim\yii2ContainerConfigurator;

use PHPUnit_Framework_TestCase;
use yii\base\Configurable;

/**
 * @package mougrim\yii2ContainerConfigurator
 * @author Mougrim <rinat@mougrim.ru>
 */
class ContainerConfiguratorTestStubYiiBaseConfigurable implements Configurable
{
    private $constructParam1;
    private $constructParam2;
    private $constructConfig;

    public function __construct($param1, $param2, array $config)
    {
        $this->constructParam1 = $param1;
        $this->constructParam2 = $param2;
        $this->constructConfig = $config;
    }

    public static function test(
        PHPUnit_Framework_TestCase $test,
        array $config,
        ContainerConfiguratorTestStubYiiBaseConfigurable $stub
    )
    {
        $test->assertEquals($config['class'], get_class($stub));

        $test->assertSame($config['arguments'][0], $stub->constructParam1);
        $test->assertSame($config['arguments'][1], $stub->constructParam2);
        $test->assertSame($config['arguments'][2], $stub->constructConfig);
    }
}
