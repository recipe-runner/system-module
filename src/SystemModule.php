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
use Symfony\Component\Process\Process;
use Yosymfony\Collection\CollectionInterface;

/**
 * System module. It contains a method for executing commands.
 *
 * @author Víctor Puertas <vpgugr@gmail.com>
 */
class SystemModule extends ModuleBase
{
    public function __construct()
    {
        parent::__construct();
        $this->addMethodHandler('run', [$this, 'run']);
    }

    /**
     * {@inheritdoc}
     */
    public function runMethod(Method $method, CollectionInterface $recipeVariables) : ExecutionResult
    {
        return $this->runInternalMethod($method, $recipeVariables);
    }
    
    /**
     * Executes a command.
     *
     * ```yaml
     * run: "echo hi"
     * ```
     *
     * or
     *
     * ```yaml
     * run:
     *   command: "echo hi"
     *   timeout: 60
     * ```
     *
     * or
     *
     * ```yaml
     * run:
     *   command:
     *       "echo"
     *       "hi"
     *   timeout: 60
     *   cwd: "/temp"
     * ```
     *
     * @param Method
     *
     * @return ExecutionResult
     */
    protected function run(Method $method): ExecutionResult
    {
        $this->validateNumberOfParameters($method);
        $process = $this->createProcess($method);
        $this->configureCommonParameters($process, $method);
        $process->run();
        
        $output = $process->getOutput();
        
        $jsonResponse = \json_encode(['output' => $output]);
 
        return new ExecutionResult($jsonResponse, $process->isSuccessful());
    }

    private function validateNumberOfParameters(Method $method): void
    {
        $parameters = $method->getParameters();
        $numberOfParameters = $parameters->count();

        if ($numberOfParameters == 0 || $numberOfParameters > 3) {
            throw new RuntimeException('Expected between 1 and 3 parameters.');
        }

        if ($numberOfParameters > 1) {
            if ($parameters->keys()->intersect(['command', 'timeout', 'cwd'])->count() != $numberOfParameters) {
                throw new InvalidArgumentException("Unexpected parameter name.");
            }
        }
    }

    private function createProcess(Method $method): Process
    {
        $parameters = $method->getParameters();

        $command = $method->getParameterNameOrPosition('command', 0);
        
        if (\is_string($command)) {
            return Process::fromShellCommandline($command);
        }

        if (\is_array($command)) {
            return new Process($command);
        }

        throw new InvalidArgumentException('Invalid command. Expected string or array value.');
    }

    private function configureCommonParameters(Process $process, Method $method): void
    {
        $parameters = $method->getParameters();

        $cwd = $parameters->getOrDefault('cwd');

        if ($cwd !== null) {
            $process->setWorkingDirectory($cwd);
        }

        $timeout = $parameters->getOrDefault('timeout', 60);

        if ($timeout !== null && (!\is_int($timeout) || (\is_int($timeout) && $timeout < 0))) {
            throw new InvalidArgumentException("Invalid timeout value. Value found: {$timeout}.");
        }

        $process->setTimeout($timeout);
    }
}
