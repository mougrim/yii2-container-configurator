<?php
namespace mougrim\yii2ContainerConfigurator;

use yii\base\Configurable;
use yii\base\Object;
use yii\di\Container;

/**
 * @package mougrim\yii2ContainerConfigurator
 * @author Mougrim <rinat@mougrim.ru>
 */
class ContainerConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigureAlias()
    {
        $config = [
            'service' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
            ],
            'alias' => 'service',
        ];

        $callParams = [];
        $container = $this->getContainerMock($callParams, 1, 1);

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['set']));
        $this->assertTrue(isset($callParams['set']['alias']));
        $this->assertEquals(1, count($callParams['set']['alias']));
        $this->assertTrue(isset($callParams['setSingleton']));
        $this->assertTrue(isset($callParams['setSingleton']['service']));
        $this->assertEquals(1, count($callParams['setSingleton']['service']));
        $stub = $callParams['setSingleton']['service'][0]($container, [], []);
        ContainerConfiguratorTestStub::test($this, $config['service'], $stub);
    }

    public function testConfigureCallable()
    {
        $config = [
            'callable' => function () {
            }
        ];

        $callParams = [];
        $container = $this->getContainerMock($callParams, 1);

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['set']));
        $this->assertTrue(isset($callParams['set']['callable']));
        $this->assertEquals(1, count($callParams['set']['callable']));
        $this->assertSame($config['callable'], $callParams['set']['callable'][0]);
        $this->assertFalse(isset($callParams['setSingleton']));
    }

    public function dataProviderConfigureService()
    {
        return [
            'default' => ['isDefaultType' => true,],
            'service' => ['isDefaultType' => false,],
        ];
    }

    /**
     * @dataProvider dataProviderConfigureService
     *
     * @param boolean $isDefaultType
     *
     * @throws WrongConfigException
     */
    public function testConfigureService($isDefaultType)
    {
        $config = [
            'service' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
            ],
        ];
        if (!$isDefaultType) {
            $config['service']['type'] = ContainerConfigurator::COMPONENT_TYPE_SERVICE;
        }

        $callParams = [];
        $container = $this->getContainerMock($callParams, 0, 1);

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['setSingleton']));
        $this->assertTrue(isset($callParams['setSingleton']['service']));
        $this->assertEquals(1, count($callParams['setSingleton']['service']));
        $this->assertTrue(is_callable($callParams['setSingleton']['service'][0]));
        $stub = $callParams['setSingleton']['service'][0]($container, [], []);
        ContainerConfiguratorTestStub::test($this, $config['service'], $stub);
        $this->assertFalse(isset($callParams['set']));
    }

    public function testConfigurePrototype()
    {
        $config = [
            'prototype' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                'type' => ContainerConfigurator::COMPONENT_TYPE_PROTOTYPE,
            ],
        ];

        $callParams = [];
        $container = $this->getContainerMock($callParams, 1);

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['set']));
        $this->assertTrue(isset($callParams['set']['prototype']));
        $this->assertEquals(1, count($callParams['set']['prototype']));
        $this->assertTrue(is_callable($callParams['set']['prototype'][0]));
        $stub = $callParams['set']['prototype'][0]($container, [], []);
        ContainerConfiguratorTestStub::test($this, $config['prototype'], $stub);
        $this->assertFalse(isset($callParams['setSingleton']));
    }

    public function dataProviderConfigure()
    {
        $referenceConfigureArguments = new ContainerConfiguratorTestStub('reference');
        $referenceConfigureProperties = new ContainerConfiguratorTestStub('reference');
        $referenceConfigureCall = new ContainerConfiguratorTestStub('reference');
        return [
            'Configure arguments' => [
                'containerConfig' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'arguments' => [
                            [
                                'id' => 'reference',
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
                            ],
                            [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [
                    'reference' => [
                        'result' => $referenceConfigureArguments,
                    ],
                ],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'arguments' => [
                        $referenceConfigureArguments,
                        'value',
                    ],
                ],
            ],
            'Configure arguments merge' => [
                'containerConfig' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'arguments' => [
                            1 => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value1',
                            ],
                            2 => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value2',
                            ],
                        ],
                    ],
                ],
                'createParams' => [
                    'value0',
                    'value1a',
                ],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => null,
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'arguments' => [
                        'value0',
                        'value1a',
                        'value2',
                    ],
                ],
            ],
            'Configure properties' => [
                'containerConfig' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'properties' => [
                            'property1' => [
                                'id' => 'reference',
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
                            ],
                            'property2' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [
                    'reference' => [
                        'result' => $referenceConfigureProperties,
                    ],
                ],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'properties' => [
                        'property1' => $referenceConfigureProperties,
                        'property2' => 'value',
                    ],
                ],
            ],
            'Configure properties merge' => [
                'containerConfig' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'properties' => [
                            'property1' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value1',
                            ],
                            'property2' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value2',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [
                    'property2' => 'value2a',
                    'property3' => 'value3',
                ],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'properties' => [
                        'property1' => 'value1',
                        'property2' => 'value2a',
                        'property3' => 'value3',
                    ],
                ],
            ],
            'Configure calls' => [
                'containerConfig' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'call' => [
                            'method1' => [
                                [
                                    'id' => 'reference',
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
                                ],
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method1value',
                                ],
                            ],
                            'method2' => [
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method2value',
                                ],
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [
                    'reference' => [
                        'result' => $referenceConfigureCall,
                    ],
                ],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'methods' => [
                        'method1' => [
                            $referenceConfigureCall,
                            'method1value',
                        ],
                        'method2' => [
                            'method2value',
                        ],
                    ],
                ],
            ],
            'Configure extend class' => [
                'containerConfig' => [
                    'parent' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    ],
                    'service' => [
                        'extends' => 'parent',
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                ],
            ],
            'Configure extend arguments' => [
                'containerConfig' => [
                    'parent' => [
                        'arguments' => [
                            [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value0',
                            ],
                            [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value1a',
                            ],
                        ],
                    ],
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'extends' => 'parent',
                        'arguments' => [
                            1 => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value1',
                            ],
                            2 => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value2',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'arguments' => [
                        'value0',
                        'value1',
                        'value2',
                    ],
                ],
            ],
            'Configure extend properties' => [
                'containerConfig' => [
                    'parent' => [
                        'properties' => [
                            'property1' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value1',
                            ],
                            'property2' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value2',
                            ],
                        ],
                    ],
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'extends' => 'parent',
                        'properties' => [
                            'property2' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value2a',
                            ],
                            'property3' => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'value3',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'properties' => [
                        'property1' => 'value1',
                        'property2' => 'value2a',
                        'property3' => 'value3',
                    ],
                ],
            ],
            'Configure extend calls' => [
                'containerConfig' => [
                    'parent' => [
                        'call' => [
                            'method1' => [
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method1value1',
                                ],
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method1value2',
                                ],
                            ],
                            'method2' => [
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method2value',
                                ],
                            ],
                        ],
                    ],
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'extends' => 'parent',
                        'call' => [
                            'method2' => [
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method2value3',
                                ],
                            ],
                            'method3' => [
                                [
                                    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                    'value' => 'method3value',
                                ],
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'createProperties' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
                'containerGetParams' => [],
                'testData' => [
                    'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                    'methods' => [
                        'method1' => [
                            'method1value1',
                            'method1value2',
                        ],
                        'method2' => [
                            'method2value3',
                        ],
                        'method3' => [
                            'method3value',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderConfigure
     *
     * @param array   $containerConfig
     * @param array   $createParams
     * @param array   $createProperties
     * @param integer $containerSetCallsQty
     * @param integer $containerSetSingletonCallsQty
     * @param array   $containerGetParams
     * @param array   $testData
     *
     * @throws WrongConfigException
     */
    public function testConfigure(
        array $containerConfig,
        array $createParams,
        array $createProperties,
        $containerSetCallsQty,
        $containerSetSingletonCallsQty,
        array $containerGetParams = null,
        array $testData
    )
    {
        $callParams = [];
        $container = $this->getContainerMock(
            $callParams,
            $containerSetCallsQty,
            $containerSetSingletonCallsQty,
            $containerGetParams
        );

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($containerConfig);

        $this->assertTrue(isset($callParams['setSingleton']));
        $this->assertTrue(isset($callParams['setSingleton']['service']));
        $this->assertEquals(1, count($callParams['setSingleton']['service']));
        $this->assertTrue(is_callable($callParams['setSingleton']['service'][0]));
        $stub = $callParams['setSingleton']['service'][0]($container, $createParams, $createProperties);
        ContainerConfiguratorTestStub::test(
            $this,
            $testData,
            $stub
        );
        $this->assertFalse(isset($callParams['set']));
    }

    public function testConfigurePrototypeExtends()
    {
        $config = [
            'parent' => [
                'type' => ContainerConfigurator::COMPONENT_TYPE_PROTOTYPE,
            ],
            'prototype' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                'extends' => 'parent',
            ],
        ];

        $callParams = [];
        $container = $this->getContainerMock(
            $callParams,
            1
        );

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['set']));
        $this->assertTrue(isset($callParams['set']['prototype']));
        $this->assertEquals(1, count($callParams['set']['prototype']));
        $this->assertTrue(is_callable($callParams['set']['prototype'][0]));
        $stub = $callParams['set']['prototype'][0]($container, [], []);
        ContainerConfiguratorTestStub::test(
            $this,
            [
                'class' => $config['prototype']['class']
            ],
            $stub
        );
        $this->assertFalse(isset($callParams['setSingleton']));
    }

    public function dataConfigurable()
    {
        return [
            'Object' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStubYiiBaseObject',
            ],
            'Configurable' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStubYiiBaseConfigurable',
            ],
        ];
    }

    /**
     * @dataProvider dataConfigurable
     *
     * @param ContainerConfiguratorTestStubYiiBaseObject|ContainerConfiguratorTestStubYiiBaseConfigurable|string $class
     *
     * @throws WrongConfigException
     */
    public function testConfigureConfigurable($class)
    {
        $config = [
            'service' => [
                'class' => $class,
                'arguments' => [
                    [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'argument1value',
                    ],
                    [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'argument2value',
                    ],
                ],
                'properties' => [
                    'property1' => [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'property1value',
                    ],
                    'property2' => [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'property2value',
                    ],
                ],
            ],
        ];

        $callParams = [];
        $container = $this->getContainerMock($callParams, 0, 1);

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['setSingleton']));
        $this->assertTrue(isset($callParams['setSingleton']['service']));
        $this->assertEquals(1, count($callParams['setSingleton']['service']));
        $this->assertTrue(is_callable($callParams['setSingleton']['service'][0]));
        $stub = $callParams['setSingleton']['service'][0]($container, [], []);
        $class::test(
            $this,
            [
                'class' => $config['service']['class'],
                'arguments' => [
                    'argument1value',
                    'argument2value',
                    [
                        'property1' => 'property1value',
                        'property2' => 'property2value',
                    ]
                ],
            ],
            $stub
        );
        $this->assertFalse(isset($callParams['set']));
    }

    public function dataProviderConfigureException()
    {
        return [
            'Missing class' => [
                'config' => [
                    'service' => [],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 0,
            ],
            'Wrong alias' => [
                'config' => [
                    'alias' => 'missing',
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 0,
            ],
            'Missing argument in config' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'arguments' => [
                            1 => [
                                'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                                'value' => 'argument2value',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
            ],
            'Missing argument in params' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'arguments' => [],
                    ],
                ],
                'createParams' => [
                    1 => [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'argument2value',
                    ],
                ],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
            ],
            'Wrong component type' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'type' => 'wrong_type',
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 0,
            ],
            'Wrong argument type' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'arguments' => [
                            [
                                'type' => 'wrong_type',
                                'value' => 'argument1value',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
            ],
            'Wrong property type' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'properties' => [
                            'property' => [
                                'type' => 'wrong_type',
                                'value' => 'propertyValue',
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
            ],
            'Wrong method param type' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'call' => [
                            'method' => [
                                [
                                    'type' => 'wrong_type',
                                    'value' => 'methodValue',
                                ]
                            ],
                        ],
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 1,
            ],
            'Missing extended' => [
                'config' => [
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'extends' => 'missing',
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 0,
            ],
            'Extends callable' => [
                'config' => [
                    'callable' => function () {
                        return 1;
                    },
                    'service' => [
                        'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                        'extends' => 'callable',
                    ],
                ],
                'createParams' => [],
                'containerSetCallsQty' => 0,
                'containerSetSingletonCallsQty' => 0,
            ],
        ];
    }

    /**
     * @expectedException \mougrim\yii2ContainerConfigurator\WrongConfigException
     * @dataProvider dataProviderConfigureException
     *
     * @param array   $config
     * @param array   $createParams
     * @param integer $containerSetCallsQty
     * @param integer $containerSetSingletonCallsQty
     *
     * @throws WrongConfigException
     */
    public function testConfigureException(
        array $config,
        array $createParams,
        $containerSetCallsQty,
        $containerSetSingletonCallsQty
    )
    {
        $callParams = [];
        $container = $this->getContainerMock($callParams, $containerSetCallsQty, $containerSetSingletonCallsQty);

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertTrue(isset($callParams['setSingleton']));
        $this->assertTrue(isset($callParams['setSingleton']['service']));
        $this->assertEquals(1, count($callParams['setSingleton']['service']));
        $this->assertTrue(is_callable($callParams['setSingleton']['service'][0]));
        $callParams['setSingleton']['service'][0]($container, $createParams, []);
        $this->assertFalse(isset($callParams['set']));
    }

    /**
     * @param array $callParams
     * @param integer $setCallsQty
     * @param integer $setSingletonCallsQty
     * @param array $getParams
     *
     * @return \PHPUnit_Framework_MockObject_MockBuilder|Container
     */
    private function getContainerMock(
        array &$callParams,
        $setCallsQty = 0,
        $setSingletonCallsQty = 0,
        array $getParams = null
    )
    {
        $test = $this;
        $container = $this->getMockBuilder('yii\di\Container')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $container->expects($this->exactly($setCallsQty))
            ->method('set')
            ->will($this->returnCallback(function (
                $class,
                $definition = [],
                array $params = []
            ) use ($container, &$callParams) {
                $this->assertEmpty($params);
                $callParams['set'][$class][] = $definition;
                return $container;
            }))
        ;
        $container->expects($this->exactly($setSingletonCallsQty))
            ->method('setSingleton')
            ->will($this->returnCallback(function (
                $class,
                $definition = [],
                array $params = []
            ) use ($container, &$callParams) {
                $this->assertEmpty($params);
                $callParams['setSingleton'][$class][] = $definition;
                return $container;
            }))
        ;

        $container->expects($this->exactly(count($getParams)))
            ->method('get')
            ->will($this->returnCallback(function (
                $class,
                $params = [],
                $config = []
            ) use ($test, $getParams) {
                $test->assertTrue(isset($getParams[$class]));
                if (!isset($getParams[$class]['params'])) {
                    $getParams[$class]['params'] = [];
                }
                if (!isset($getParams[$class]['config'])) {
                    $getParams[$class]['config'] = [];
                }
                $this->assertEquals($getParams[$class]['params'], $params);
                $this->assertEquals($getParams[$class]['config'], $config);
                return $getParams[$class]['result'];
            }))
        ;
        return $container;
    }
}

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

    public static function test(ContainerConfiguratorTest $test, array $config, ContainerConfiguratorTestStub $stub)
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

class ContainerConfiguratorTestStubYiiBaseObject extends Object
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
        ContainerConfiguratorTest $test,
        array $config,
        ContainerConfiguratorTestStubYiiBaseObject $stub
    )
    {
        $test->assertEquals($config['class'], get_class($stub));

        $test->assertSame($config['arguments'][0], $stub->constructParam1);
        $test->assertSame($config['arguments'][1], $stub->constructParam2);
        $test->assertSame($config['arguments'][2], $stub->constructConfig);
    }
}

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
        ContainerConfiguratorTest $test,
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
