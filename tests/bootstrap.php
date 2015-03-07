<?php
/**
 * @author Mougrim <rinat@mougrim.ru>
 */
// ensure we get report on all possible php errors
error_reporting(-1);

// require composer autoloader
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    /** @noinspection PhpIncludeInspection */
    require_once($composerAutoload);
} else {
    /** @noinspection PhpIncludeInspection */
    require_once(__DIR__ . '/../../../autoload.php');
}

require_once __DIR__ . '/unit/mougrim/yii2ContainerConfigurator/ContainerConfiguratorTestStub.php';
require_once __DIR__ . '/unit/mougrim/yii2ContainerConfigurator/ContainerConfiguratorTestStubYiiBaseObject.php';
if (interface_exists('yii\base\Configurable')) {
    require_once __DIR__ . '/unit/mougrim/yii2ContainerConfigurator/ContainerConfiguratorTestStubYiiBaseConfigurable.php';
}
