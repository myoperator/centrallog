# MyOperator CentralLog

This library is intended to be used as a basic `log4php` wrapper to log in our desired pattern.

## Features

* Namespaces and defines
* PSR-4 autoloading compliant structure
* Default detail log compatible configurator
* One log location centrally

## Installation
You can easily install this package by adding following section in your composer.json:

```
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/myoperator/centrallog.git"
        }
    ]
```
and then doing: `composer require myoperator/centrallog:dev-master`

or by adding following to your composer.json:

```
  "require": {
        "myoperator/centrallog": "dev-master"
  }
```

The `composer.json` will look like

```json
{
    "require": {
        "myoperator/centrallog": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/myoperator/centrallog.git"
        }
    ]
}
```

## Usage

1. Include `vendor/autoload` in your project
```php
   include_once 'vendor/autoload.php';
```

2. Configure the logger

```php
  use \MyOperator\Centrallog;

  CentralLog::configure('mylog.log'); //logs.log refers to log output file
```

3. Get the logger and log anything
```php
  $log = CentralLog::getLogger('myLogger');
  $log->log("Something");
```

Overall, this can be summarised as 

```php
include_once 'vendor/autoload.php';
use \MyOperator\CentralLog;

CentralLog::configure("mylog.log");

$log = CentralLog::getLogger('myLogger');
$log->log("Something");
```

## Configurations

The logger is adjusted to be configured as per myoperator specific logs. Hence, following params can be passed to the `configure` method.

```php
  CentralLog::configure(string  $outputpath = null, string  $server = null, string|\MyOperator\class  $class = null, string  $pattern = null, string  $maxsize = null)
```
Parameters

```php
string	$outputpath	Output path of the log file

string	$server	Server on which the application is running. Ex- S6, API01

string	$class	Class name under which the logger is being used

string	$pattern	logger pattern as described in https://logging.apache.org/log4php/docs/layouts/pattern.html

string	$maxsize	Maximum size per log
```

## Available methods

### Logging General log

Any log can be logged with following method signature

```php
   CentralLog::log(mixed  $message, integer  $acl = null, string  $uid = null) 
```

Parameters
```php
mixed	$message	Item to be logged

integer	$acl	The ACL to be used to log the item. (optional)

string	$uid	The unique id of item. In case of sync script, this can be engine uid. (optional). Can be one of [1,2,4]
```

Note that none of support/developer/client log method needs `$acl` parameter as it is obvious which `$acl` is going to be used
### Logging support logs
```php
   $logger->slog(mixed  $message, string  $uid = null, string  $level = null)
```

### Logging client logs
```php
   $logger->clog(mixed  $message, string  $uid = null, string  $level = null)
```

### Logging developer logs
```php
   $logger->dlog(mixed  $message, string  $uid = null, string  $level = null);
```

## Viewing documentation

This package uses phpdoc to generate documentation. You can generate the package documentation by cloning the repository and installing dev dependencies

```sh
    composer update --dev
```

and then using `phpdoc` to generate reference documentation by

```
   phpdoc -d src/
```


## Todo
* Add phpunit testcases
