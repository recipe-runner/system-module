<?php

namespace RecipeRunner\SystemModule;

use InvalidArgumentException;
use RecipeRunner\RecipeRunner\Module\Invocation\ExecutionResult;
use RecipeRunner\RecipeRunner\Module\Invocation\Method;
use RecipeRunner\RecipeRunner\Module\ModuleBase;
use RuntimeException;
use Symfony\Component\Process\Process;
use Yosymfony\Collection\CollectionInterface;

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
     * Write a message to the output.
     *
     * ```yaml
     * run: "echo hi"
     * ```
     * or
     * ```yaml
     * run:
     *   command: "echo hi"
     *   timeout: 60
     * ```
     * or
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
     * @return ExecutionResult Result with an empty JSON.
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
        $numberOfParameters = $method->getParameters()->count();

        if ($numberOfParameters >= 1 && $numberOfParameters < 3) {
            return;
        }

        throw new RuntimeException('Expected between 1 and 3 parameters.');
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

        if (!\is_int($timeout) || (\is_int($timeout) && $timeout < 0)) {
            throw new InvalidArgumentException("Invalid timeout value. Value found: {$timeout}.");
        }

        $process->setTimeout($timeout);
    }
}
