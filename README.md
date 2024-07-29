# Repository is archived
If you want to maintain it, you can make a fork.

# Container configurator Extension for Yii 2

This extension can configure di container for yii2 with simple php-array and use lazy load.

For license information check the [LICENSE](LICENSE)-file.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mougrim/yii2-container-configurator
```

or add

```json
"mougrim/yii2-container-configurator": "*"
```

to the `require` section of your composer.json.

Edit file config/main.php:

```php
<?php
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;
use yii\di\Container;
...

Yii::$container->set(
    'containerConfigurator',
    function (Container $container) {
        $containerConfigurator = new ContainerConfigurator($container);
        $containerConfigurator->configure(require __DIR__ . '/container.php'); // di container config path
        return $containerConfigurator;
    }
);

...

return [
    ...
    'bootstrap'  => [
        ...
        'containerConfigurator'
    ],
    'components' => [
        'containerConfigurator' => 'containerConfigurator',
        ...
    ],
];
```

And create config/container.php:

```php
<?php
return [
    // your di container config
]
```

## Usage

### Callback

You can use callbacks in container config:

```php
<?php
return [
    'app' => function () {
        return Yii::$app;
    },
]; 
```

This callback will call in all get:

```php
Yii::$container->get('app');
```

### Services
 
By default components created as service, i.e. created once in first get:

```php
<?php
use yii\web\Response;

return [
    'front.response' => [
        'class' => Response::class, // class name
    ],
];
```
 
### Prototypes

If you want for every get created new instance, you can use prototype:

```php
<?php
use yii\web\Response;
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;

return [
    'front.response' => [
        'class' => Response::class, // class name
        'type' => ContainerConfigurator::COMPONENT_TYPE_PROTOTYPE,
    ],
];
```

### Aliases

If you want add alias of service or prototype:
```php
<?php
use yii\web\Response;
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;

return [
    'front.response' => [
        'class' => Response::class, // class name
    ],
    'front.response-alias' => 'front.response',
];
```

Now this:

```php
<?php
Yii::$container->get('front.response-alias');
```

equivalent this:

```php
<?php
Yii::$container->get('front.response');
```

### Arguments format

#### Argument type reference

If you want inject from di, you can use parameter type reference:

```php
[
    'id' => 'front.request',
    'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
]
```

#### Argument type value

If you want inject some value, you can use parameter type value:

```php
[
    'type' => ContainerConfigurator::ARGUMENT_TYPE_VALUE,
    'value' => 'some value',
],
```

### Pass arguments to constructor

If you want pass arguments to constructor, you can use 'arguments':


```php
<?php
use frontend\controllers\SiteController;
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;

return [
    'controllers.site' => [
        'class' => SiteController::class,
        'arguments' => [
            2 => [ // argument number
                'id' => 'front.request',
                'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
            ],
        ],
    ],
];
```

SiteController:

```php
<?php
namespace frontend\controllers;

use yii\base\Module;
use yii\web\Controller
use yii\web\Request;

class SiteController extends Controller
{
    private $request;

    public function __construct(
        $id,
        Module $module,
        Request $request,
        array $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->request = $request;
    }
}
```

And add to controller map in config/main.php:

```php
    ...
    'controllerMap' => [
        ...
        'site' => 'controllers.site',
    ],
    ...
```

### Set properties

If you want set properties, you can use 'properties':

```php
<?php
use frontend\controllers\SiteController;
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;

return [
    'controllers.site' => [
        'class' => SiteController::class,
        'properties' => [
            'request' => [
                'id' => 'front.request',
                'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
            ],
        ],
    ],
];
```

### Call callback

If you want call callbacks, you can use 'call', but in yii objects (extends from \yii\base\Object) callback will call after method init:

```php
<?php
use frontend\controllers\SiteController;
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;

return [
    'controllers.site' => [
        'class' => SiteController::class,
        'call' => [
            'setRequest' => [ 
                [
                    'id' => 'front.request',
                    'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
                ],
            ],
        ],
    ],
];
```

### Extends

If you want extend config, you can use 'extends':

```php
<?php
use frontend\controllers\SiteController;
use mougrim\yii2ContainerConfigurator\ContainerConfigurator;

return [
    'components.controller' => [
        'type' => ContainerConfigurator::COMPONENT_TYPE_PROTOTYPE,
        'properties' => [
            'app' => [
                'id' => 'app',
                'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
            ],
        ],
    ],
    'controllers.site' => [
        'class' => SiteController::class,
        'extends' => 'components.controller',
        'arguments' => [
            2 => [
                'id' => 'front.request',
                'type' => ContainerConfigurator::ARGUMENT_TYPE_REFERENCE,
            ],
        ],
    ],
];
```
