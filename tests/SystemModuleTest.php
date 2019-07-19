<?php

namespace RecipeRunner\SystemModule\Test;

use PHPUnit\Framework\TestCase;
use RecipeRunner\RecipeRunner\IO\IOInterface;
use RecipeRunner\RecipeRunner\Module\Invocation\ExecutionResult;
use RecipeRunner\RecipeRunner\Module\Invocation\Method;
use RecipeRunner\SystemModule\SystemModule;
use Yosymfony\Collection\MixedCollection;

class SystemModuleTest extends TestCase
{
    /** @var SystemModule */
    private $module;

    public function setUp(): void
    {
        $IOMock = $this->getMockBuilder(IOInterface::class)->getMock();
        $this->module = new SystemModule();
        $this->module->setIO($IOMock);
    }

    public function testMethodRunSuccessfully()
    {
        $method = new Method('run');
        $method->addParameter(0, 'echo hi');

        $executionResult = $this->module->runMethod($method, new MixedCollection());

        $this->assertTrue($executionResult->isSuccess());
    }

    public function testMethodRunNotSuccessfully()
    {
        $method = new Method('run');
        $method->addParameter(0, 'echo-fake hi');

        $executionResult = $this->module->runMethod($method, new MixedCollection());

        $this->assertFalse($executionResult->isSuccess());
    }

    public function testMethodRunMustReturnTheOutput()
    {
        $method = new Method('run');
        $method->addParameter(0, 'echo hi');

        $executionResult = $this->module->runMethod($method, new MixedCollection());

        $this->assertEquals([
            'output' => "hi\n"
        ], $this->getResultAsArray($executionResult));
    }

    public function testMethodRunWithParameterCommandAsString()
    {
        $method = new Method('run');
        $method->addParameter('command', 'echo hi');

        $executionResult = $this->module->runMethod($method, new MixedCollection());

        $this->assertTrue($executionResult->isSuccess());
    }

    public function testMethodRunWithParameterCommandAsArray()
    {
        $method = new Method('run');
        $method->addParameter('command', ["echo", "hi"]);

        $executionResult = $this->module->runMethod($method, new MixedCollection());

        $this->assertTrue($executionResult->isSuccess());
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected between 1 and 3 parameters.
    */
    public function testRunMustFailWhenThereAreNoParameters(): void
    {
        $method = new Method('run');

        $executionResult = $this->module->runMethod($method, new MixedCollection());
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected between 1 and 3 parameters.
    */
    public function testRunMustFailWhenThereAreMoreThan3Parameters(): void
    {
        $method = new Method('run');
        $method->addParameter('param1', '')
            ->addParameter('param2', '')
            ->addParameter('param3', '')
            ->addParameter('param4', '');

        $executionResult = $this->module->runMethod($method, new MixedCollection());
    }

    /**
    * @expectedException InvalidArgumentException
    * @expectedExceptionMessage Unexpected parameter name.
    */
    public function testRunMustFailWhenThereAreMoreThan1ParameterAndTheirKeysAreNotValid(): void
    {
        $method = new Method('run');
        $method->addParameter('command', '')
            ->addParameter('fake', '')
            ->addParameter('timeout', '');

        $executionResult = $this->module->runMethod($method, new MixedCollection());
    }

    /**
    * @expectedException InvalidArgumentException
    * @expectedExceptionMessage Invalid command. Expected string or array value.
    */
    public function testRunMustFailWhenCommandParameterIsNotStringOrArray(): void
    {
        $method = new Method('run');
        $method->addParameter('command', 1);

        $executionResult = $this->module->runMethod($method, new MixedCollection());
    }

    private function getResultAsArray(ExecutionResult $result): array
    {
        return \json_decode($result->getJsonResult(), true);
    }
}
