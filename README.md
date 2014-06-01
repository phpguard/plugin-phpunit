# PHPUnit Plugin

PHPUnit plugin for PhpGuard

[![License](https://poser.pugx.org/phpguard/plugin-phpunit/license.png)](https://packagist.org/packages/phpguard/plugin-phpunit)
[![Latest Stable Version](https://poser.pugx.org/phpguard/plugin-phpunit/v/stable.png)](https://packagist.org/packages/phpguard/plugin-phpunit)
[![HHVM Status](http://hhvm.h4cc.de/badge/phpguard/plugin-phpunit.png)](http://hhvm.h4cc.de/package/phpguard/plugin-phpunit)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpguard/plugin-phpunit/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpguard/plugin-phpunit/?branch=master)
[![Master Build Status](https://secure.travis-ci.org/phpguard/plugin-phpunit.png?branch=master)](http://travis-ci.org/phpguard/plugin-phpunit)
[![Coverage Status](https://coveralls.io/repos/phpguard/plugin-phpunit/badge.png?branch=master)](https://coveralls.io/r/phpguard/plugin-phpunit?branch=master)

# Installation

Using composer:
```shell
$ cd /paths/to/project
$ composer require --dev "phpguard/plugin-phpunit @dev"
```

# Options
Complete configuration options for `PHPUnit` plugin:
* `cli` The options to passed to the phpunit command. Default is: Default: `null`
* `all_on_start` Run all tests on startup. Default: `false`
* `all_after_pass` Run all tests after changed tests pass. Default: `false`
* `keep_failed` Remember failed tests and keep running them until pass. Default: `false`
* `always_lint` Always check file syntax with `php -l` before run. If check syntax failed, `phpunit` command will not running. Default: `true`
* `run_all_cli` The options to passed to the phpunit command. Default value will be using `cli` options

# Full Configuration Sample
```yaml
# /path/to/project/phpguard.yml
phpunit:
    options:
        cli:            "--testdox --colors"
        all_on_start:   false
        all_after_pass: false
        keep_failed:    true
        always_lint:    true
        run_all_cli:    "--colors"
    watch:
        - { pattern: "#^src\/(.+)\.php$#", transform: "functional/${1}Test.php" }
        - { pattern: "#^functional\/.*Test.php$#" }
```