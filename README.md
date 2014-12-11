### Clown:

A simple ActiveRecord library for PHP.

### Getting Started:

* Create composer.json file in root directory of your application:

```json
{
    "require": {
        "php": ">=5.4.0",
        "nanjingboy/clown": "*"
    }
}
```
* Install it via [composer](https://getcomposer.org/doc/00-intro.md)

* Init the config in your bootstrap.php:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
Clown\Config::init($configPath);

$classLoader = new ClassLoader();
$classLoader->addPrefix('Models', $modelsDirectory);
$classLoader->register();
```

* Get a config example from [test](https://github.com/nanjingboy/clown/tree/master/test/configs/test/clown.php)

* Get api from [wiki](https://github.com/nanjingboy/clown/wiki)

### License:

MIT
