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
                    'assetic_write_to' => $asseticWriteTo
                ]]
            ]
        ]);
        
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('asset.js');
        $collection->add($this->makeStringAsset('asset1.js'));
        $collection->add($this->makeStringAsset('asset2.js'));
        $collection->add($this->makeStringAsset('asset3.js'));
        $collection = $worker->process($collection, $factory);

        $this->assertEquals(<<<EOT
console.log("string asset for asset1.js");console.log("string asset for asset2.js");console.log("string asset for asset3.js");
//# sourceMappingURL=www.coursehero.com/sym-assets/asset.js.map
EOT
        , $collection->dump());
        
        $sourceMap = file_get_contents("$asseticWriteTo/asset.js.map");
        $this->assertEquals(<<<EOT
{"version":3,"sources":["\/asset1.js","\/asset2.js","\/asset3.js"],"names":["console","log"],"mappings":"AAAAA,QAAQC,IAAI,8BCAZD,QAAQC,IAAI,8BCAZD,QAAQC,IAAI","file":"asset.js","sourceRoot":"sources:\/\/\/","sourcesContent":["console.log('string asset for asset1.js');","console.log('string asset for asset2.js');","console.log('string asset for asset3.js');"]}
EOT
        , $sourceMap);
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
                    'source_map_source_path_trim' => '/test/'
                ]]
            ]
        ]);
        
        $factory = $this->createMock(AssetFactory::class);

        $collection = new AssetCollection();
        $collection->setTargetPath('expected.js');
        $collection->add($this->makeStringAsset('asset1.js'));
        $collection->add(new FileAsset(dirname(__FILE__) . '/test.js'));
        $collection->add(new HttpAsset('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.js'));
        $collection = $worker->process($collection, $factory);

        $this->assertEquals(file_get_contents('tests/expected.js'), $collection->dump());
        
        $sourceMap = $this->loadSourceMap("$asseticWriteTo/expected.js.map");
        $this->assertEquals($this->loadSourceMap('tests/expected.js.map'), $sourceMap);
    }

    private function loadSourceMap($path)
    {
        $json = file_get_contents($path);
        return json_encode(json_decode($json, true), JSON_PRETTY_PRINT);
    }

    private function makeStringAsset($sourcePath)
    {
        $asset = new StringAsset("console.log('string asset for $sourcePath');", [], null, $sourcePath);
        return $asset;
    }
}
