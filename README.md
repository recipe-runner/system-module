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

### Method: `copy_file`

Makes a copy of a single file.

```yaml
steps:
    - actions:
        copy_file:
            from: "/dir1/file.txt"
            to: "/tmp/file.txt"
```

### Method: `mirror_dir`

Copies all the contents of the source directory into the target one.

```yaml
steps:
    - actions:
        mirror_dir:
            from: "/dir1"
            to: "/tmp"
```

### Method: `read_file`

Read the content of a file.

```yaml
steps:
    - actions:
        read_file: "/tmp/hi.txt"
        register: "file_content"
```

File content available at `content`:

```yml
registered["file_content"]["content"]

## or

registered.get('file_content.content')
```

### Method: `write_file`

Saves the given contents into a file.

```yaml
steps:
    - actions:
        write_file:
            filename: "/tmp/hi.txt"
            content: "hi user"
```

## For module developers

The preferred installation method is [Composer](https://getcomposer.org):

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
