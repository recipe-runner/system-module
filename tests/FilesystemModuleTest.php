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

    /** @var FilesystemModule */
    private $moduleWithCurlDisabled;

    /** @var MockObject */
    private $fsMock;

    public function setUp(): void
    {
        $IOMock = $this->getMockBuilder(IOInterface::class)->getMock();
        $this->fsMock = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
        $this->module = new FilesystemModule($this->fsMock);
        $this->module->setIO($IOMock);
        $this->moduleWithCurlDisabled = new FilesystemModule($this->fsMock, false);
        $this->moduleWithCurlDisabled->setIO($IOMock);
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
     * @testWith    [true]
     *              [false]
     */
    public function testDownloadFile(bool $curlEnabled): void
    {
        $rootDir = vfsStream::setup();
        $filename = 'test.phar';
        $filenamePath = $rootDir->url()."/$filename";
        $urlFile = 'https://github.com/spress/Spress/releases/download/v2.2.0/spress.phar';
        $method = new Method('download_file');
        $method->addParameter('url', $urlFile)
            ->addParameter('filename', $filenamePath);

        $result = $this->runMethod($method, $curlEnabled);

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($rootDir->hasChild($filename));
    }

    /**
     * @testWith    [true]
     *              [false]
     */
    public function testDowndloadFileMustReturnFileWhenURLNotFound(bool $curlEnabled): void
    {
        $rootDir = vfsStream::setup();
        $filename = 'test.phar';
        $filenamePath = $rootDir->url()."/$filename";
        $urlFile = 'https://github.com/spress/Spress/releases/not-found/spress.phar';
        $method = new Method('download_file');
        $method->addParameter('url', $urlFile)
            ->addParameter('filename', $filenamePath);

        $result = $this->runMethod($method, $curlEnabled);

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($rootDir->hasChild($filename));
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage The URL "test.phar" is not valid.
    */
    public function testDownloadFileMustFailWhenURLIsNotValid(): void
    {
        $urlFile = 'test.phar';
        $method = new Method('download_file');
        $method->addParameter('url', $urlFile)
            ->addParameter('filename', 'test.phar');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected 2 parameters.
    */
    public function testDownloadFileMustFailWhenTheNumberOfParamsIsNotExactly2(): void
    {
        $method = new Method('download_file');
        $method->addParameter('url', 'http://blabla.com/file.txt')
            ->addParameter('filename', 'file.txt')
            ->addParameter('param3', 'bla blat');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage is not recognized.
    *
    * @testWith ["url"]
    *           ["filename"]
    */
    public function testDownloadFileMustFailWhenThereIsAnInvalidParamName(string $validParamName): void
    {
        $method = new Method('download_file');
        $method->addParameter($validParamName, 'value')
            ->addParameter('unexpected', 'bla bla');

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
    public function testDownloadFileMustFailWhenFromOrToIsNotAStringValueOrItIsEmptyString($url, $filename): void
    {
        $method = new Method('download_file');
        $method->addParameter('url', $url)
            ->addParameter('filename', $filename);

        $this->runMethod($method);
    }

    /**
     * @testWith    [0]
     *              ["dir"]
     */
    public function testMakeDirWithDefaultMode($parameterName): void
    {
        $dir = '/dir1';
        $method = new Method('make_dir');
        $method->addParameter($parameterName, $dir);
        $this->fsMock->expects($this->once())->method('mkdir')->with($this->equalTo($dir), $this->equalTo(0777));

        $this->runMethod($method);
    }

    /**
     * @testWith    [755]
     *              [744]
     */
    public function testMakeDirWithMode(int $mode): void
    {
        $dir = '/dir1';
        $method = new Method('make_dir');
        $method->addParameter('dir', $dir)
            ->addParameter('mode', $mode);

        $this->fsMock->expects($this->once())->method('mkdir')->with($this->equalTo($dir), $this->equalTo($mode));

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected between 1 and 2 parameters.
    */
    public function testMakeDirMustFailWhenTheNumberOfParamIsLessThan1(): void
    {
        $method = new Method('make_dir');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected between 1 and 2 parameters.
    */
    public function testMakeDirMustFailWhenTheNumberOfParamIsGreaterThan2(): void
    {
        $method = new Method('make_dir');
        $method->addParameter('dir', '/tmp2')
            ->addParameter('mode', 0777)
            ->addParameter('extra', 'bla bla');

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage is not recognized.
    */
    public function testMakeDirMustFailWhenThereIsAnInvalidParamName(): void
    {
        $method = new Method('mirror_dir');
        $method->addParameter('dir', '/tmp/1')
            ->addParameter('unexpected', '/tmp/2');

        $this->runMethod($method);
    }

    /**
    * @expectedException InvalidArgumentException
    * @expectedExceptionMessage Parameter "mode" is expected as integer.
    */
    public function testMakeDirMustFailWhenModeIsNotAnIntergerValue(): void
    {
        $method = new Method('make_dir');
        $method->addParameter('dir', '/tmp/1')
            ->addParameter('mode', 'bad value');

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

    public function testRemove(): void
    {
        $files = ['file1', 'file2'];
        $method = new Method('remove');
        $method->addParameter(0, $files[0])
            ->addParameter(1, $files[1]);

        $this->fsMock->expects($this->once())->method('remove')->with($this->equalTo($files));

        $this->runMethod($method);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage Expected at least 1 parameters.
    */
    public function testRemoveMustFailWhenTheNumberOfParametersIsZero(): void
    {
        $method = new Method('remove');

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

    private function runMethod(Method $method, bool $curlEnabled = true): ExecutionResult
    {
        if ($curlEnabled) {
            return $this->module->runMethod($method, new MixedCollection());
        }
        
        return $this->moduleWithCurlDisabled->runMethod($method, new MixedCollection());
    }
}
