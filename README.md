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

## Todo

* Add documentation to export public methods
* Add phpunit testcases
