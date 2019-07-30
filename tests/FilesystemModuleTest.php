<?php

/*
 * This file is part of the "Recipe Runner" project.
 *
 * (c) Víctor Puertas <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RecipeRunner\RecipeRunner\IO\IOInterface;
use RecipeRunner\RecipeRunner\Module\Invocation\ExecutionResult;
use RecipeRunner\RecipeRunner\Module\Invocation\Method;
use RecipeRunner\SystemModule\FilesystemModule;
use Symfony\Component\Filesystem\Filesystem;
use Yosymfony\Collection\MixedCollection;

class FilesystemModuleTest extends TestCase
{
    /** @var FilesystemModule */
    private $module;

    /** @var MockObject */
    private $fsMock;

    public function setUp(): void
    {
        $IOMock = $this->getMockBuilder(IOInterface::class)->getMock();
        $this->fsMock = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
        $this->module = new FilesystemModule($this->fsMock);
        $this->module->setIO($IOMock);
    }

    public function testCopyFile(): void
    {
        $fileFrom = '/dir1/file.txt';
        $fileTo = '/dir2/file.txt';
        $method = new Method('copy_file');
        $method->addParameter('from', $fileFrom)
            ->addParameter('to', $fileTo);
        $this->fsMock->expects($this->once())->method('copy')->with($this->equalTo($fileFrom), $this->equalTo($fileTo));

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected 2 parameters.
    */
    public function testCopyFileMustFailWhenTheNumberOfParamIsNotExactly2(): void
    {
        $method = new Method('copy_file');
        $method->addParameter('from', 'file.txt')
            ->addParameter('to', 'file.txt')
            ->addParameter('param3', 'bla blat');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage is not recognized.
    *
    * @testWith ["from"]
    *           ["to"]
    */
    public function testCopyFileMustFailWhenThereIsAnInvalidParamName(string $validParamName): void
    {
        $method = new Method('copy_file');
        $method->addParameter($validParamName, 'file.txt')
            ->addParameter('unexpected', 'file.txt');

        $this->runMethod($method);
    }

    /**
    * @expectedException InvalidArgumentException
    *
    * @testWith [1,1]
    *           [true, true]
    *           ["", ""]
    *           ["  ", "  "]
    *           [null, null]
    */
    public function testCopyFileMustFailWhenFromOrToIsNotAStringValueOrItIsEmptyString($fromValue, $toValue): void
    {
        $method = new Method('copy_file');
        $method->addParameter('from', $fromValue)
            ->addParameter('to', $toValue);

        $this->runMethod($method);
    }

    public function testMirrorDirMustCopyFromDirAToDirB(): void
    {
        $dirFrom = '/dir1';
        $dirTo = '/dir2';
        $method = new Method('mirror_dir');
        $method->addParameter('from', $dirFrom)
            ->addParameter('to', $dirTo);
        $this->fsMock->expects($this->once())->method('mirror')->with($this->equalTo($dirFrom), $this->equalTo($dirTo));

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected 2 parameters.
    */
    public function testMirrorDirMustFailWhenTheNumberOfParametersIsNotExactly2(): void
    {
        $method = new Method('mirror_dir');
        $method->addParameter('from', '/dir1')
            ->addParameter('to', '/temp')
            ->addParameter('param3', 'bla blat');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage is not recognized.
    *
    * @testWith ["from"]
    *           ["to"]
    */
    public function testMirrorDirMustFailWhenThereIsAnInvalidParamName(string $validParamName): void
    {
        $method = new Method('mirror_dir');
        $method->addParameter($validParamName, '/tmp/1')
            ->addParameter('unexpected', '/tmp/2');

        $this->runMethod($method);
    }

    /**
    * @expectedException InvalidArgumentException
    *
    * @testWith [1,1]
    *           [true, true]
    *           ["", ""]
    *           ["  ", "  "]
    *           [null, null]
    */
    public function testMirrorDirMustFailWhenFromOrToIsNotAStringValueOrItIsEmptyString($fromValue, $toValue): void
    {
        $method = new Method('mirror_dir');
        $method->addParameter('from', $fromValue)
            ->addParameter('to', $toValue);

        $this->runMethod($method);
    }

    public function testWriteFile(): void
    {
        $filename = '/temp/file.txt';
        $content = 'hi user';

        $method = new Method('write_file');
        $method->addParameter('filename', $filename)
            ->addParameter('content', $content);

        $this->fsMock->expects($this->once())->method('dumpFile')->with($this->equalTo($filename), $this->equalTo($content));

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected 2 parameters.
    */
    public function testWriteFileMustFailWhenTheNumberOfParametersIsNotExactly2(): void
    {
        $method = new Method('write_file');
        $method->addParameter('filename', '/file.txt')
            ->addParameter('content', 'bla bla')
            ->addParameter('param3', 'bla blat');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage is not recognized.
    *
    * @testWith ["filename"]
    *           ["content"]
    */
    public function testWriteFileMustFailWhenThereIsAnInvalidParamName(string $validParamName): void
    {
        $method = new Method('write_file');
        $method->addParameter($validParamName, '/file.txt')
            ->addParameter('unexpected', 'bla bla');

        $this->runMethod($method);
    }

    /**
    * @expectedException InvalidArgumentException
    *
    * @testWith [1]
    *           [[]]
    *           [null]
    *           [""]
    *           [" "]
    */
    public function testWriteFileMustFailWhenFilenameIsNotAStringOrItIsEmpty($filename): void
    {
        $method = new Method('write_file');
        $method->addParameter('filename', $filename)
            ->addParameter('content', 'bla bla');

        $this->runMethod($method);
    }

    /**
     * @testWith    ["filename"]
     *              [0]
     */
    public function testReadFile($parameterName): void
    {
        $filename = 'test.txt';
        $expectedContent = 'bla bla';

        $rootDir = vfsStream::setup();
        vfsStream::create([
            $filename => $expectedContent,
        ]);
        $method = new Method('read_file');
        $method->addParameter($parameterName, $rootDir->url()."/{$filename}");

        $result = $this->runMethod($method);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals([
            'content' => $expectedContent,
        ], \json_decode($result->getJsonResult(), true));
    }

    public function testReadFileMustFailIfFilenameDoesNotExist(): void
    {
        $filename = 'test.txt';
        $expectedContent = 'bla bla';

        $rootDir = vfsStream::setup();
        $method = new Method('read_file');
        $method->addParameter('filename', $rootDir->url()."/{$filename}");

        $result = $this->runMethod($method);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals([
            'content' => null,
        ], \json_decode($result->getJsonResult(), true));
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected 1 parameters.
    */
    public function testReadFileMustFailWhenTheNumberOfParametersIsNotExactly1(): void
    {
        $method = new Method('read_file');
        $method->addParameter('filename', '/file.txt')
            ->addParameter('param2', 'bla blat');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Parameter "unexpected" is not recognized.
    */
    public function testReadFileMustFailWhenThereIsAnUnexpectedParameterName(): void
    {
        $method = new Method('read_file');
        $method->addParameter('unexpected', '/file.txt');

        $this->runMethod($method);
    }

    private function runMethod(Method $method): ExecutionResult
    {
        return $this->module->runMethod($method, new MixedCollection());
    }
}