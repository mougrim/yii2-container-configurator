<?php
namespace mougrim\yii2ContainerConfigurator;

use yii\di\Container;
use ReflectionClass;

/**
 * @package common\components\di
 * @author Mougrim <rinat@mougrim.ru>
 */
class ContainerConfigurator
{
    const COMPONENT_TYPE_SERVICE = 'service';
    const COMPONENT_TYPE_PROTOTYPE = 'prototype';

    const ARGUMENT_TYPE_REFERENCE = 'reference';
    const ARGUMENT_TYPE_VALUE = 'value';

    private $container;
    /**
     * @var ReflectionClass[]
     */
    private $reflections = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * The following is an example for registering components definitions:
     *
     * ```php
     * [
     *     // callback, call in every inject
     *     'app' => function () {
     *         return Yii::$app;
     *     },
     *     // alias of app
     *     'app-alias' => 'app',
     *     'front.response' => [
     *         'class' => Response::class,
     *     ],
     *     'front.request' => [
     *         'class' => Request::class,
     *         // set properties
     *         'properties' => [
     *             'cookieValidationKey' => [
     *                 // type value, set as is
     *                 'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
     *                 'value' => '',
     *             ],
     *         ],
     *     ],
     *     'frontend.components.controller' => [
     *         // default component type service - this is singleton in yii di, if prototype - create new instance in every inject
     *         'type' => ContainerConfigurator::COMPONENT_TYPE_PROTOTYPE,
     *         'properties' => [
     *             'app' => [
     *                 // id referenced component
     *                 'id' => 'app',
     *                 // type reference - use another component
     *                 'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
     *             ],
     *         ],
     *     ],
     *     'frontend.controllers.site' => [
     *         'class' => SiteController::class,
     *         // extend another component
     *         'extends' => 'frontend.components.controller',
     *         // pass to constructor
     *         'arguments' => [
     *             2 => [ // argument number
     *                 'id' => 'front.request',
     *                 'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
     *             ],
     *             3 => [
     *                 'id' => 'front.response',
     *                 'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
     *             ],
     *         ],
     *     ],
     * ]
     * ```
     *
     * @param array $config container config
     *
     * @throws WrongConfigException
     */
    public function configure($config)
    {
        $config = $this->prepare($config);
        $configurator = $this;
        foreach ($config as $id => $classConfig) {
            // alias
            if (is_string($classConfig)) {
                if (!isset($config[$classConfig])) {
                    throw new WrongConfigException("Component '{$classConfig}' missing for alias '{$id}'");
                }
                $this->container->set($id, $classConfig);
                continue;
            }
            if (is_callable($classConfig, true)) {
                $this->container->set($id, $classConfig);
                continue;
            }

            if (!isset($classConfig['class'])) {
                throw new WrongConfigException("'class' is require param in class config for '{$id}'");
            }

            if (!isset($classConfig['type'])) {
                $classConfig['type'] = static::COMPONENT_TYPE_SERVICE;
            }

            if (!isset($classConfig['arguments'])) {
                $classConfig['arguments'] = [];
            }

            /** @noinspection PhpUnusedParameterInspection */
            /**
             * @param Container $container
             * @param array     $params
             * @param array     $config
             *
             * @return object
             * @throws WrongConfigException
             */
            $factoryFunction = function (
                Container $container,
                array $params,
                array $config
            ) use($configurator, $id, $classConfig) {
                if (!isset($configurator->reflections[$classConfig['class']])) {
                    $configurator->reflections[$classConfig['class']] = new ReflectionClass($classConfig['class']);
                }
                $properties = $configurator->resolveProperties($id, $classConfig['properties'], $config);
                $arguments = [];
                $argumentNumber = 0;
                while (isset($params[$argumentNumber]) || isset($classConfig['arguments'][$argumentNumber])) {
                    if (isset($params[$argumentNumber])) {
                        $arguments[$argumentNumber] = $params[$argumentNumber];
                    } else {
                        $arguments[$argumentNumber] = $configurator->resolveArgument(
                            $id,
                            $classConfig['arguments'][$argumentNumber]
                        );
                    }
                    unset($params[$argumentNumber]);
                    unset ($classConfig['arguments'][$argumentNumber]);
                    $argumentNumber++;
                }

                if (!empty($params) || !empty($classConfig['arguments'])) {
                    throw new WrongConfigException("Constructor argument '{$argumentNumber}' is missing");
                }
                if (
                    is_a($classConfig['class'], 'yii\base\Object', true) ||
                    is_a($classConfig['class'], 'yii\base\Configurable', true)
                ) {
                    // set $config as the last parameter (existing one will be overwritten)
                    $arguments[count($arguments)] = $properties;
                    $object = $configurator->reflections[$classConfig['class']]->newInstanceArgs($arguments);
                } else {
                    $object = $configurator->reflections[$classConfig['class']]->newInstanceArgs($arguments);
                    foreach ($properties as $propertyName => $propertyValue) {
                        $object->$propertyName = $propertyValue;
                    }
                }
                foreach ($classConfig['call'] as $methodName => $methodArgumentsInfo) {
                    call_user_func_array(
                        [$object, $methodName],
                        $configurator->resolveArguments(
                            $id,
                            $methodArgumentsInfo
                        )
                    );
                }

                return $object;
            };

            if ($classConfig['type'] === static::COMPONENT_TYPE_SERVICE) {
                $this->container->setSingleton($id, $factoryFunction);
            } elseif ($classConfig['type'] === static::COMPONENT_TYPE_PROTOTYPE) {
                $this->container->set($id, $factoryFunction);
            } else {
                throw new WrongConfigException("Unknown class type '{$classConfig['type']}'");
            }
        }
    }

    private function prepare(array $config) {
        $parents = [];
        foreach($config as $id => &$classConfig) {
            if (is_callable($classConfig, true)) {
                continue;
            }

            if(isset($classConfig['extends'])) {
                $parents[$classConfig['extends']] = $classConfig['extends'];
            }

            $classConfig = $this->resolveExtends($config, $id, $classConfig);

            if (!isset($classConfig['call'])) {
                $classConfig['call'] = [];
            }
            if (!isset($classConfig['properties'])) {
                $classConfig['properties'] = [];
            }
        }
        unset($classConfig);

        foreach ($parents as $parent) {
            unset ($config[$parent]);
        }

        return $config;
    }

    private function resolveExtends($config, $id, $classConfig)
    {
        $parentClassConfig = $classConfig;
        while(isset($parentClassConfig['extends'])) {
            if (!isset($config[$parentClassConfig['extends']])) {
                throw new WrongConfigException("Missing extends link '{$parentClassConfig['extends']}  for '{$id}'");
            }
            $id = $parentClassConfig['extends'];
            $parentClassConfig = $config[$parentClassConfig['extends']];
            if (is_callable($parentClassConfig, true)) {
                throw new WrongConfigException("Can't extend callback in '{$id}'");
            }
            if (!isset($classConfig['class']) && isset($parentClassConfig['class'])) {
                $classConfig['class'] = $parentClassConfig['class'];
            }
            if (!isset($classConfig['type']) && isset($parentClassConfig['type'])) {
                $classConfig['type'] = $parentClassConfig['type'];
            }
            if (isset($parentClassConfig['arguments'])) {
                foreach ($parentClassConfig['arguments'] as $argumentNumber => $argumentInfo) {
                    if (!isset($classConfig['arguments'][$argumentNumber])) {
                        $classConfig['arguments'][$argumentNumber] = $argumentInfo;
                    }
                }
            }
            if (isset($parentClassConfig['call'])) {
                foreach ($parentClassConfig['call'] as $methodName => $methodArgumentsInfo) {
                    if (!isset($classConfig['call'][$methodName])) {
                        $classConfig['call'][$methodName] = $methodArgumentsInfo;
                    }
                }
            }
            if (isset($parentClassConfig['properties'])) {
                foreach ($parentClassConfig['properties'] as $propertyName => $propertyInfo) {
                    if (!isset($classConfig['properties'][$propertyName])) {
                        $classConfig['properties'][$propertyName] = $propertyInfo;
                    }
                }
            }
        }

        return $classConfig;
    }

    private function resolveProperties($id, array $propertiesInfo, array $config)
    {
        $properties = [];
        foreach ($propertiesInfo as $propertyName => $propertyInfo) {
            if (isset($config[$propertyName])) {
                $properties[$propertyName] = $config[$propertyName];
                unset($config[$propertyName]);
            } else {
                $properties[$propertyName] = $this->resolveArgument($id, $propertyInfo);
            }
        }

        foreach ($config as $name => $value) {
            $properties[$name] = $value;
        }

        return $properties;
    }

    private function resolveArguments($id, array $argumentsInfo)
    {
        $arguments = [];
        foreach ($argumentsInfo as $argumentInfo) {
            $arguments[] = $this->resolveArgument($id, $argumentInfo);
        }

        return $arguments;
    }

    private function resolveArgument($id, array $argumentInfo)
    {
        if($argumentInfo['type'] === static::ARGUMENT_TYPE_VALUE) {
            $argument = $argumentInfo['value'];
        } elseif ($argumentInfo['type'] === static::ARGUMENT_TYPE_REFERENCE) {
            $argument = $this->container->get($argumentInfo['id']);
        } else {
            throw new WrongConfigException("Unknown argument type '{$argumentInfo['type']}' in '{$id}'");
        }

        return $argument;
    }
}
