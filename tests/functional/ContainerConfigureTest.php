<?php
namespace functional;

use mougrim\yii2ContainerConfigurator\ContainerConfigurator;
use mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub;
use mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStubYiiBaseConfigurable;
use mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStubYiiBaseObject;
use mougrim\yii2ContainerConfigurator\WrongConfigException;
use yii\di\Container;

/**
 * @package functional
 * @author Mougrim <rinat@mougrim.ru>
 */
class ContainerConfigureTest extends \PHPUnit_Framework_TestCase
{
    public function testService()
    {
        $config = [
            'service' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
            ],
        ];

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertSame($container->get('service'), $container->get('service'));
        /** @var ContainerConfiguratorTestStub $service */
        $service = $container->get('service');
        ContainerConfiguratorTestStub::test(
            $this,
            [
                'class' => $config['service']['class'],
            ],
            $service
        );
    }

    public function testPrototype()
    {
        $config = [
            'prototype' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                'type' => ContainerConfigurator::COMPONENT_TYPE_PROTOTYPE,
            ],
        ];

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertNotSame($container->get('prototype'), $container->get('prototype'));
        /** @var ContainerConfiguratorTestStub $prototype */
        $prototype = $container->get('prototype');
        ContainerConfiguratorTestStub::test(
            $this,
            [
                'class' => $config['prototype']['class'],
            ],
            $prototype
        );
    }

    public function testCallable()
    {
        $callsQty = 0;
        $objects = [
            new ContainerConfigureTest(),
            new ContainerConfigureTest(),
        ];
        $config = [
            'callable' => function () use (&$callsQty, $objects) {
                $object = $objects[$callsQty];
                $callsQty++;
                return $object;
            },
        ];

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $object1 = $container->get('callable');
        $object2 = $container->get('callable');
        $this->assertSame($objects[0], $object1);
        $this->assertSame($objects[1], $object2);
        $this->assertNotSame($object1, $object2);
        $this->assertEquals(2, $callsQty);
    }

    public function testAlias()
    {
        $config = [
            'service' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
            ],
            'alias' => 'service',
        ];

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        $this->assertSame($container->get('service'), $container->get('alias'));
    }

    public function testResolveArgument()
    {
        $config = [
            'reference' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
            ],
            'service' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                'arguments' => [
                    [
                        'id' => 'reference',
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
                    ],
                ],
            ],
        ];

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        /** @var ContainerConfiguratorTestStub $service */
        $service = $container->get('service');
        ContainerConfiguratorTestStub::test(
            $this,
            [
                'class' => $config['service']['class'],
                'arguments' => [
                    $container->get('reference'),
                ],
            ],
            $service
        );
    }

    public function testCallbackMakesByContainerConfigurator()
    {
        $config = [
            'service' => [
                'class' => 'mougrim\yii2ContainerConfigurator\ContainerConfiguratorTestStub',
                'arguments' => [
                    [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'argument0Value',
                    ],
                    [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'argument1Value',
                    ],
                ],
                'properties' => [
                    'property1' => [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'property1Value',
                    ],
                    'property2' => [
                        'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
                        'value' => 'property2Value',
                    ],
                ],
            ],
        ];

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        /** @var ContainerConfiguratorTestStub $service */
        $service = $container->get(
            'service',
            [
                1 => 'argument1ValueA',
                2 => 'argument2Value',
            ],
            [
                'property2' => 'property2ValueA',
                'property3' => 'property3Value',
            ]
        );
        ContainerConfiguratorTestStub::test(
            $this,
            [
                'class' => $config['service']['class'],
                'arguments' => ['argument0Value','argument1ValueA','argument2Value'],
                'properties' => [
                    'property1' => 'property1Value',
                    'property2' => 'property2ValueA',
                    'property3' => 'property3Value',
                ],
            ],
            $service
        );
    }

    public function dataProviderConfigurable()
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
     * @dataProvider dataProviderConfigurable
     *
     * @param ContainerConfiguratorTestStubYiiBaseObject|ContainerConfiguratorTestStubYiiBaseConfigurable|string $class
     *
     * @throws WrongConfigException
     */
    public function testConfigurable($class)
    {
        if (!class_exists($class, false)) {
            $this->markTestSkipped("Class '{$class}' not found, maybe yii2 old version, it's ok");
        }

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

        $container = new Container();

        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure($config);

        /** @var ContainerConfiguratorTestStubYiiBaseObject|ContainerConfiguratorTestStubYiiBaseConfigurable $stub */
        $stub = $container->get('service');
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
    }
}
