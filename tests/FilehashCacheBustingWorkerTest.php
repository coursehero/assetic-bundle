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
        $actualTargetPath = $this->getCacheBustingTargetPath('testAsset.txt', [
            new FileAsset(__DIR__ . '/cache-busting/testAsset.txt')
        ]);
        $this->assertEquals($actualTargetPath, 'testAsset-019b8b3.txt');
    }

    public function testHashMultipleFiles()
    {
        $actualTargetPath = $this->getCacheBustingTargetPath('testAsset.txt', [
            new FileAsset(__DIR__ . '/cache-busting/testAsset.txt'),
            new FileAsset(__DIR__ . '/cache-busting/testAsset2.txt')
        ]);
        $this->assertEquals($actualTargetPath, 'testAsset-ff6345f.txt');
    }

    public function testHashScssImports()
    {
        $targetPath1 = $this->getCacheBustingTargetPath('main.css', [
            new FileAsset(__DIR__ . '/cache-busting/scss-1/main.scss')
        ]);
        $targetPath2 = $this->getCacheBustingTargetPath('main.css', [
            new FileAsset(__DIR__ . '/cache-busting/scss-2/main.scss')
        ]);
        $this->assertNotEquals($targetPath1, $targetPath2, 'Hash did not take imported files into account');
    }

    private function getCacheBustingTargetPath(string $targetPath, array $files)
    {
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath($targetPath);
        foreach ($files as $file) {
            $collection->add($file);
        }

        $this->worker->process($collection, $factory);
        return $collection->getTargetPath();
    }
}
