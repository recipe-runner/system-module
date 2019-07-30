<?php

/*
 * This file is part of the "Recipe Runner" project.
 *
 * (c) Víctor Puertas <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RecipeRunner\SystemModule;

use InvalidArgumentException;
use RecipeRunner\RecipeRunner\Module\Invocation\ExecutionResult;
use RecipeRunner\RecipeRunner\Module\Invocation\Method;
use RecipeRunner\RecipeRunner\Module\ModuleBase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Yosymfony\Collection\CollectionInterface;
use Yosymfony\Collection\MixedCollection;

/**
 * Module that provides methods for the filesystem
 *
 * @author Víctor Puertas <vpgugr@gmail.com>
 */
class FilesystemModule extends ModuleBase
{
    /** @var Filesystem */
    private $fs;

    public function __construct(Filesystem $fs = null)
    {
        parent::__construct();
        $this->fs = $fs ?? new Filesystem();
        $this->addMethodHandler('copy_file', [$this, 'copyFile']);
        $this->addMethodHandler('mirror_dir', [$this, 'mirrorDir']);
        $this->addMethodHandler('write_file', [$this, 'writeFile']);
        $this->addMethodHandler('read_file', [$this, 'readFile']);
    }

    /**
     * {@inheritdoc}
     */
    public function runMethod(Method $method, CollectionInterface $recipeVariables) : ExecutionResult
    {
        return $this->runInternalMethod($method, $recipeVariables);
    }

    /**
     * Makes a copy of a single file.
     *
     * @param Method $method Method information.
     *
     * Parameters:
     *  - from: The origin file
     *  - to: The target file
     *
     * @return ExecutionResult Execution result.
     *
     * Variables: none.
     */
    protected function copyFile(Method $method): ExecutionResult
    {
        $isSuccessful = true;
        $parameters = $method->getParameters();

        $this->assertNumberOfParametersIs($parameters, 2);
        $this->assertParameterNamesMustBeIn($parameters, new MixedCollection(['from', 'to']));
        $this->assertParameterIsNotEmptyString($parameters, 'from');
        $this->assertParameterIsNotEmptyString($parameters, 'to');

        $fromFile = $parameters->get('from');
        $toFile = $parameters->get('to');

        try {
            $this->fs->copy($fromFile, $toFile);
        } catch (\Throwable $th) {
            $isSuccessful = false;
        }

        return new ExecutionResult(ExecutionResult::EMPTY_JSON, $isSuccessful);
    }

    /**
     * Copies all the contents of the source directory into the target one.
     *
     * @param Method $method Method information.
     *
     * Parameters:
     *  - from: The origin directory
     *  - to: The target directory
     *
     * @return ExecutionResult Execution result.
     *
     * Variables: none.
     */
    protected function mirrorDir(Method $method): ExecutionResult
    {
        $isSuccessful = true;
        $parameters = $method->getParameters();

        $this->assertNumberOfParametersIs($parameters, 2);
        $this->assertParameterNamesMustBeIn($parameters, new MixedCollection(['from', 'to']));
        $this->assertParameterIsNotEmptyString($parameters, 'from');
        $this->assertParameterIsNotEmptyString($parameters, 'to');

        $fromDir = $parameters->get('from');
        $toDir = $parameters->get('to');

        try {
            $this->fs->mirror($fromDir, $toDir);
        } catch (\Throwable $th) {
            $isSuccessful = false;
        }

        return new ExecutionResult(ExecutionResult::EMPTY_JSON, $isSuccessful);
    }

    /**
     * Read the content of a file.
     *
     * @param Method $method Method information.
     *
     * Parameters:
     *  - filename: The file to be read.
     *
     * @return ExecutionResult Execution result.
     *
     * variables:
     *  - content: the content of the file.
     */
    protected function readFile(Method $method): ExecutionResult
    {
        $isSuccessful = true;
        $parameters = $method->getParameters();

        $this->assertNumberOfParametersIs($parameters, 1);
        $this->assertParameterNamesMustBeIn($parameters, new MixedCollection(['filename', 0]));

        $fileContent = null;
        $filename = $method->getParameterNameOrPosition('filename', 0);

        try {
            $fileContent = file_get_contents($filename);
        } catch (\Throwable $th) {
            $isSuccessful = false;
        }

        $jsonResponse = \json_encode(['content' => $fileContent]);

        return new ExecutionResult($jsonResponse, $isSuccessful);
    }

    /**
     * Saves the given contents into a file.
     *
     * @param Method $method Method information.
     *
     * Parameters:
     *  - filename: The file to be written to.
     *  - content: The data to be write into the file.
     *
     * @return ExecutionResult Execution result.
     *
     * Variables: none.
     */
    protected function writeFile(Method $method): ExecutionResult
    {
        $isSuccessful = true;
        $parameters = $method->getParameters();

        $this->assertNumberOfParametersIs($parameters, 2);
        $this->assertParameterNamesMustBeIn($parameters, new MixedCollection(['filename', 'content']));
        $this->assertParameterIsNotEmptyString($parameters, 'filename');

        $filename = $parameters->get('filename');
        $content = $parameters->get('content');

        try {
            $this->fs->dumpFile($filename, $content);
        } catch (\Throwable $th) {
            $isSuccessful = false;
        }

        return new ExecutionResult(ExecutionResult::EMPTY_JSON, $isSuccessful);
    }

    private function assertNumberOfParametersIs(CollectionInterface $parameters, int $number): void
    {
        if ($parameters->count() != $number) {
            throw new RuntimeException("Expected {$number} parameters.");
        }
    }

    private function assertParameterNamesMustBeIn(CollectionInterface $parameters, CollectionInterface $expectedParameters): void
    {
        $parameterKeys = $parameters->keys();

        foreach ($parameterKeys as $key) {
            if (!$expectedParameters->any(function ($expectedParam) use ($key) {
                return $expectedParam === $key;
            })) {
                throw new RuntimeException("Parameter \"{$key}\" is not recognized.");
            }
        }
    }

    private function assertParameterIsNotEmptyString(CollectionInterface $parameters, $parameterName)
    {
        $value = $parameters->get($parameterName);

        if (!\is_string($value)) {
            throw new InvalidArgumentException("Expected a string as parameter \"{$parameterName}\".");
        }

        if (\trim($value) == '') {
            throw new InvalidArgumentException("Parameter \"{$parameterName}\" cannot be white space or empty.");
        }
    }
}
