<?php
declare(strict_types=1);

namespace CourseHero\AsseticBundle\Tests;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\StringAsset;
use Assetic\Factory\AssetFactory;
use CourseHero\AsseticBundle\Assetic\FlattenWorker;
use CourseHero\AsseticBundle\Assetic\SourceMapFilter;
use PHPUnit\Framework\TestCase;

class SourceMapFilterTest extends TestCase
{
    public function testSourceMapFilter()
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
        $collection->add($this->makeAsset('asset1.js'));
        $collection->add($this->makeAsset('asset2.js'));
        $collection->add($this->makeAsset('asset3.js'));

        $collection = $worker->process($collection, $factory);
        $assetBag = array_values($collection->all())[0];

        $this->assertEquals(<<<EOT
console.log("string asset for asset1.js");console.log("string asset for asset2.js");console.log("string asset for asset3.js");
//# sourceMappingURL=www.coursehero.com/sym-assets/asset.js.map
EOT
        , $assetBag->dump());
        
        $sourceMap = file_get_contents("$asseticWriteTo/asset.js.map");
        $this->assertEquals(<<<EOT
{"version":3,"sources":["\/asset1.js","\/asset2.js","\/asset3.js"],"names":["console","log"],"mappings":"AAAAA,QAAQC,IAAI,8BCAZD,QAAQC,IAAI,8BCAZD,QAAQC,IAAI","file":"asset.js","sourceRoot":"sources:\/\/\/","sourcesContent":["console.log('string asset for asset1.js');","console.log('string asset for asset2.js');","console.log('string asset for asset3.js');"]}
EOT
        , $sourceMap);
    }

    private function makeAsset($sourcePath)
    {
        $asset = new StringAsset("console.log('string asset for $sourcePath');", [], null, $sourcePath);
        return $asset;
    }
}
