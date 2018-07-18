<?php
declare(strict_types=1);

namespace CourseHero\AsseticBundle\Tests;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Factory\AssetFactory;
use CourseHero\AsseticBundle\Assetic\FilehashCacheBustingWorker;
use PHPUnit\Framework\TestCase;

class FilehashCacheBustingWorkerTest extends TestCase
{
    private $worker;

    protected function setUp()
    {
        $this->worker = new FilehashCacheBustingWorker();
    }

    protected function tearDown()
    {
        $this->worker = null;
    }

    public function testHashIndividualFile()
    {
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('testAsset.txt');
        $collection->add(new FileAsset(__DIR__ . '/testAsset.txt'));

        $this->worker->process($collection, $factory);
        $this->assertEquals($collection->getTargetPath(), 'testAsset-019b8b3.txt');
    }

    public function testHashMultipleFiles()
    {
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('testAsset.txt');
        $collection->add(new FileAsset(__DIR__ . '/testAsset.txt'));
        $collection->add(new FileAsset(__DIR__ . '/testAsset2.txt'));

        $this->worker->process($collection, $factory);
        $this->assertEquals($collection->getTargetPath(), 'testAsset-ff6345f.txt');
    }
}
