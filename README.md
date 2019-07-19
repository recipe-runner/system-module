# System module

[![Build Status](https://img.shields.io/travis/recipe-runner/system-module/master.svg?style=flat-square)](https://travis-ci.org/recipe-runner/system-module)

## Requires

* PHP +7.2
* [Recipe Runner](https://github.com/recipe-runner/recipe-runner)

## Installation

Create a recipe and add the module to the `packages` section:

```yaml
name: "Your recipe"
extra:
  rr:
    packages:
      "recipe-runner/system-module": "1.0.x-dev"
```

## Usage

### Method: `run`

Executes a command.

```yaml
steps:
    - actions:
        - run: "echo hi user"
```

A command could be split into command and parameters. This way, parameters will be escaped automatically.

```yaml
steps:
    - actions:
        run:
            command:
                "echo"      # Command
                "hi user"   # parameter 1
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

## For module developers

The preferred installation method is [composer](https://getcomposer.org):

```bash
composer require recipe-runner/system-module
```

### Unit tests

You can run the unit tests with the following command:

```bash
$ cd system-module
$ composer test
```

## License

This library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
