# System module

[![Build Status](https://img.shields.io/travis/recipe-runner/system-module/master.svg?style=flat-square)](https://travis-ci.org/recipe-runner/system-module)

## Requires

* PHP +7.2
* [Recipe Runner](https://github.com/recipe-runner/recipe-runner)

## Installation

The preferred installation method is [composer](https://getcomposer.org):

```bash
composer require recipe-runner/system-module
```

## Usage

### Method: `run`

Write a message to the output.

```yaml
steps:
    - actions:
        - run: "echo hi user"
```

A command could be split into command and parameters. This way of configurin a command will escape parameters automatically.

```yaml
steps:
    - actions:
        run:
            command:
                "echo"
                "hi user"
```

You can also set a timeout and the *current working directory*

```yaml
steps:
    - actions:
        run:
            command: "echo hi user"
            timeout: 60
            cwd: "/temp"
```

## Unit tests

You can run the unit tests with the following command:

```bash
$ cd system-module
$ composer test
```

## License

This library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
