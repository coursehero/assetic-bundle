<?php
declare(strict_types=1);

namespace CourseHero\AsseticBundle\Tests;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\StringAsset;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\FilterInterface;
use CourseHero\AsseticBundle\Assetic\CHAssetBag;
use CourseHero\AsseticBundle\Assetic\FlattenWorker;
use PHPUnit\Framework\TestCase;

class FlattenWorkerTest extends TestCase
{
    public function testAssetsUnchangedWithNoMatchingFilter()
    {
        $worker = new FlattenWorker([
            [
                'match' => '/\.js$/',
                'class' => TestFilter::class,
                'args' => [1, 'two', 3]
            ]
        ]);
        
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->add($this->makeAsset('asset1.txt'));
        $collection->add($this->makeAsset('asset2.txt'));
        $collection->add($this->makeAsset('asset3.png'));

        $collection = $worker->process($collection, $factory);
        $this->assertNull($collection);
    }

    public function testAssetsUnchangedWithSomeNotMatchingFilter()
    {
        $worker = new FlattenWorker([
            [
                'match' => '/\.txt$/',
                'class' => TestFilter::class,
                'args' => [1, 'two', 3]
            ]
        ]);
        
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->add($this->makeAsset('asset1.txt'));
        $collection->add($this->makeAsset('asset2.txt'));
        $collection->add($this->makeAsset('asset3.png'));

        $collection = $worker->process($collection, $factory);
        $this->assertNull($collection);
    }

    public function testMovesAllAssetsToAssetBagWhenMatchingFilter()
    {
        $worker = new FlattenWorker([
            [
                'match' => '/\.txt$/',
                'class' => TestFilter::class,
                'args' => [1, 'two', 3]
            ]
        ]);
        
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->add($this->makeAsset('asset1.txt'));
        $collection->add($this->makeAsset('asset2.txt'));
        $collection->add($this->makeAsset('asset3.txt'));

        $collection = $worker->process($collection, $factory);
        $this->assertEquals(count($collection->all()), 1);

        $assetBag = array_values($collection->all())[0];
        $this->assertEquals(get_class($assetBag), CHAssetBag::class);
        $this->assertEquals(count($assetBag->getFilters()), 1);
        $this->assertEquals(get_class($assetBag->getFilters()[0]), TestFilter::class);
        $this->assertEquals($assetBag->getFilters()[0]->vars, [1, 'two', 3]);
    }

    private function makeAsset($sourcePath)
    {
        $asset = new StringAsset("string asset for $sourcePath", [], null, $sourcePath);
        return $asset;
    }
}

class TestFilter implements FilterInterface
{
    public $vars = [];

    public function __construct(int $arg1, string $arg2, int $arg3)
    {
        $this->vars[] = $arg1;
        $this->vars[] = $arg2;
        $this->vars[] = $arg3;
    }

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
    }
}
