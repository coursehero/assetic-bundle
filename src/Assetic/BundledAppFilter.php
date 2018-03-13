<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

class BundledAppFilter implements FilterInterface
{
    /**
     * @inheritdoc
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * @inheritdoc
     */
    public function filterDump(AssetInterface $asset)
    {
        $content = $asset->getContent();

        // var_dump("in the filter yo \n");
        // var_dump($asset->getSourcePath());
        // $root = rtrim($this->asseticDir, '/');
        $root = $asset->getSourceRoot();
        $symAssetsRoot = "/var/www/html/coursehero/src/Control/sym-assets/";

        $targetPathOnDisk = $symAssetsRoot . str_replace('_controller/', '', $asset->getTargetPath());

        $from = $root . '/' . $asset->getSourcePath() . '.map';
        $to = $targetPathOnDisk . '.map';
        copy($from, $to);

        $asset->setContent($content . '; console.log("LOL!");' . "console.log('$from');" . "console.log('$to');");
        // $asset->setContent($content . "\n/// sourceMappingURL=" . basename($targetPathOnDisk));
    }
}
