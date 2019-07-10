<?php
declare(strict_types=1);

namespace CourseHero\AsseticBundle\Tests;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Asset\StringAsset;
use Assetic\Factory\AssetFactory;
use CourseHero\AsseticBundle\Assetic\FlattenWorker;
use CourseHero\AsseticBundle\Assetic\SourceMapFilter;
use PHPUnit\Framework\TestCase;

// http://sokra.github.io/source-map-visualization
class SourceMapFilterTest extends TestCase
{
    public function testSourceMapFilterSimple()
    {
        $asseticWriteTo = sys_get_temp_dir();
        $worker = new FlattenWorker([
            [
                'match' => '/\.js$/',
                'class' => SourceMapFilter::class,
                'args' => [[
                    'site_url' => 'www.coursehero.com/sym-assets',
                    'assetic_write_to' => $asseticWriteTo,
                    'source_map_source_path_trim' => dirname(__DIR__)
                ]]
            ]
        ]);
        
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('asset.js');
        $collection->add($this->makeStringAsset(__DIR__ . '/simple', 'asset1.js'));
        $collection->add($this->makeStringAsset(__DIR__ . '/simple', 'asset2.js'));
        $collection->add($this->makeStringAsset(__DIR__ . '/simple', 'asset3.js'));
        $collection->add($this->makeStringAsset('/../..', 'climb-above-root.js'));
        $collection = $worker->process($collection, $factory);

        $this->assertEquals(file_get_contents('tests/simple/expected.js'), $collection->dump());
        
        $sourceMap = $this->loadSourceMap("$asseticWriteTo/asset.js.map");
        $this->assertEquals($this->loadSourceMap('tests/simple/expected.js.map'), $sourceMap);
    }

    public function testSourceMapFilterComplex()
    {
        $asseticWriteTo = sys_get_temp_dir();
        $worker = new FlattenWorker([
            [
                'match' => '/\.js$/',
                'class' => SourceMapFilter::class,
                'args' => [[
                    'site_url' => 'www.coursehero.com/sym-assets',
                    'assetic_write_to' => $asseticWriteTo,
                    'source_map_source_path_trim' => dirname(__DIR__)
                ]]
            ]
        ]);

        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('expected.js');
        $collection->add($this->makeStringAsset(__DIR__ . '/complex', 'asset1.js'));
        $collection->add(new FileAsset(__DIR__ . '/complex/test.js'));
        $collection->add(new HttpAsset('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.js'));
        $collection = $worker->process($collection, $factory);

        $this->assertEquals(file_get_contents('tests/complex/expected.js'), $collection->dump());

        $sourceMap = $this->loadSourceMap("$asseticWriteTo/expected.js.map");
        $this->assertEquals($this->loadSourceMap('tests/complex/expected.js.map'), $sourceMap);
    }

    // uglifyjs can use an input file's source map, if provided inline
    public function testSourceMapFilterComposedSourceMap()
    {
        $asseticWriteTo = sys_get_temp_dir();
        $worker = new FlattenWorker([
            [
                'match' => '/\.js$/',
                'class' => SourceMapFilter::class,
                'args' => [[
                    'site_url' => 'www.coursehero.com/sym-assets',
                    'assetic_write_to' => $asseticWriteTo,
                    'source_map_source_path_trim' => dirname(__DIR__)
                ]]
            ]
        ]);

        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('composed.js');
        $collection->add($this->makeStringAsset(__DIR__ . '/composed', 'asset1.js'));
        $collection->add(new FileAsset(dirname(__FILE__) . '/composed/ts.js'));
        $collection = $worker->process($collection, $factory);

        $this->assertEquals(file_get_contents('tests/composed/expected.js'), $collection->dump());

        $sourceMap = $this->loadSourceMap("$asseticWriteTo/composed.js.map");
        $this->assertEquals($this->loadSourceMap('tests/composed/expected.js.map'), $sourceMap);
    }

    private function loadSourceMap(string $path)
    {
        $json = file_get_contents($path);
        return json_encode(json_decode($json, true), JSON_PRETTY_PRINT);
    }

    private function makeStringAsset(string $sourceRoot, string $sourcePath)
    {
        $asset = new StringAsset("console.log('string asset for $sourcePath');", [], $sourceRoot, $sourcePath);
        return $asset;
    }
}
