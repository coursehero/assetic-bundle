<?php
declare(strict_types=1);

namespace CourseHero\AsseticBundle\Tests;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetFactory;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetCollectionInterface;
use CourseHero\AsseticBundle\Assetic\FilehashCacheBustingWorker;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class FilehashCacheBustingWorkerTest extends TestCase
{
    private $worker;

    protected function setUp()
    {
        $this->worker = new FilehashCacheBustingWorker();
        echo(AssetFactory::class);
        var_dump(new \Assetic\Asset\AssetFactory());
    }

    protected function tearDown()
    {
        $this->worker = null;
    }

    /**
     * @test
     */
    public function shouldHashIndividualFile(){
        $path = dirname(__FILE__);

        $asset = $this->createMock(AssetInterface::class);
        // $factory = $this->getMockBuilder(AssetFactory::class)
        //     ->disableOriginalConstructor()
        //     ->getMock();
        
        $factory = $this->createMock(AssetFactory::class);
        $asset->expects($this->any())
            ->method('getTargetPath')
            ->will($this->returnValue('testAsset.txt'));
        $asset->expects($this->any())
            ->method('getSourceRoot')
            ->will($this->returnValue($path));
        $asset->expects($this->any())
            ->method('getSourcePath')
            ->will($this->returnValue('testAsset.txt'));


        $asset->expects($this->once())
            ->method('setTargetPath')
            ->with($this->equalTo('testAsset-51fe62f.txt'));

        $this->worker->process($asset, $factory);
    }

    /**
     * @test
     */
    public function shouldHashMultipleFiles(){
        $path = dirname(__FILE__);

        $factory = $this->getMockBuilder(AssetFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $col = $this->createMock(AssetCollectionInterface::class);
        $col->expects($this->any())
            ->method('getTargetPath')
            ->will($this->returnValue('collection.txt'));

        $asset = $this->createMock(AssetInterface::class);
        $asset->expects($this->any())
            ->method('getSourceRoot')
            ->will($this->returnValue($path));
        $asset->expects($this->any())
            ->method('getSourcePath')
            ->will($this->returnValue('testAsset.txt'));

        $asset2 = $this->createMock(AssetInterface::class);
        $asset2->expects($this->any())
            ->method('getSourceRoot')
            ->will($this->returnValue($path));
        $asset2->expects($this->any())
            ->method('getSourcePath')
            ->will($this->returnValue('testAsset2.txt'));

        $col->expects($this->atLeastOnce())
            ->method('all')
            ->willReturn(array($asset, $asset2));

        $col->expects($this->once())
            ->method('setTargetPath')
            ->with($this->equalTo('collection-a8371fd.txt'));

        $this->worker->process($col, $factory);
    }

    /**
     * @test
     */
    public function shouldFallbackToSourcePathIfFileDoesntExist(){
        $path = dirname(__FILE__);

        $asset = $this->createMock(AssetInterface::class);
        $factory = $this->getMockBuilder(AssetFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $asset->expects($this->any())
            ->method('getTargetPath')
            ->will($this->returnValue('imaginaryAsset.txt'));

        $asset->expects($this->any())
            ->method('getSourceRoot')
            ->will($this->returnValue($path));
        $asset->expects($this->any())
            ->method('getSourcePath')
            ->will($this->returnValue('imaginaryAsset.txt'));


        $asset->expects($this->once())
            ->method('setTargetPath')
            ->with($this->equalTo('imaginaryAsset-e02df4c.txt'));

        $this->worker->process($asset, $factory);
    }

}